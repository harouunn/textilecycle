<?php

namespace Tests\Feature\Depot;

use App\Enums\Depot\Moderation;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_and_refused_clothes_are_not_in_the_catalogue(): void
    {
        Vetement::factory()->create(['titre' => 'Chemise validée']);
        Vetement::factory()->enAttente()->create(['titre' => 'Chemise en attente']);
        Vetement::factory()->refuse()->create(['titre' => 'Chemise refusée']);

        $response = $this->get(route('depot.catalogue.index'));

        $response->assertSeeText('Chemise validée');
        $response->assertDontSeeText('Chemise en attente');
        $response->assertDontSeeText('Chemise refusée');
    }

    public function test_pending_clothing_page_is_only_visible_to_its_depositor(): void
    {
        $deposant = User::factory()->create();
        $vetement = Vetement::factory()->for($deposant)->enAttente()->create();

        $this->get(route('depot.catalogue.show', $vetement))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('depot.catalogue.show', $vetement))->assertNotFound();
        $this->actingAs($deposant)->get(route('depot.catalogue.show', $vetement))->assertOk();
    }

    public function test_admin_approves_a_deposit_which_appears_in_the_catalogue(): void
    {
        $vetement = Vetement::factory()->enAttente()->create(['titre' => 'Pull rayé']);

        $response = $this->actingAs(User::factory()->create())->patch(route('admin.depot.vetements.approuver', $vetement));

        $response->assertRedirect(route('admin.depot.vetements.show', $vetement));
        $response->assertSessionHas('success', 'Le vêtement « Pull rayé » est approuvé : il apparaît dans le catalogue.');
        $this->assertSame(Moderation::Approuve, $vetement->fresh()->moderation);
        $this->get(route('depot.catalogue.index'))->assertSeeText('Pull rayé');
    }

    public function test_admin_refuses_a_deposit_with_a_reason_shown_to_the_depositor(): void
    {
        $deposant = User::factory()->create();
        $vetement = Vetement::factory()->for($deposant)->enAttente()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.depot.vetements.refuser', $vetement), ['motif_refus' => 'La photo est floue.'])
            ->assertRedirect(route('admin.depot.vetements.show', $vetement));

        $vetement->refresh();
        $this->assertSame(Moderation::Refuse, $vetement->moderation);
        $this->actingAs($deposant)->get(route('depot.mes-depots.index'))
            ->assertSeeText('Refusé')
            ->assertSeeText('La photo est floue.');
    }

    public function test_refusal_requires_a_reason(): void
    {
        $vetement = Vetement::factory()->enAttente()->create();

        $response = $this->actingAs(User::factory()->create())->patch(route('admin.depot.vetements.refuser', $vetement), ['motif_refus' => '']);

        $response->assertSessionHasErrors(['motif_refus' => 'Le champ motif du refus est obligatoire.']);
        $this->assertSame(Moderation::EnAttente, $vetement->fresh()->moderation);
    }

    public function test_editing_a_deposit_sends_it_back_for_validation(): void
    {
        $deposant = User::factory()->create();
        $vetement = Vetement::factory()->for($deposant)->refuse()->create();

        $this->actingAs($deposant)->put(route('depot.mes-depots.update', $vetement), [
            'categorie_id' => Categorie::factory()->create()->id,
            'titre' => 'Jean droit',
            'description' => 'Nouvelle photo, bien nette.',
            'taille' => 'L',
            'genre' => 'unisexe',
            'matiere' => 'Denim',
            'etat' => 'bon',
            'date_depot' => now()->format('Y-m-d'),
        ]);

        $vetement->refresh();
        $this->assertSame(Moderation::EnAttente, $vetement->moderation);
        $this->assertNull($vetement->motif_refus);
    }

    public function test_admin_list_filters_pending_deposits_and_menu_shows_their_count(): void
    {
        Vetement::factory()->create(['titre' => 'Robe publiée']);
        Vetement::factory(2)->enAttente()->sequence(['titre' => 'Veste à valider'], ['titre' => 'Jupe à valider'])->create();

        $response = $this->actingAs(User::factory()->create())->get(route('admin.depot.vetements.index', ['moderation' => 'en_attente']));

        $response->assertSeeText('Veste à valider');
        $response->assertDontSeeText('Robe publiée');
        $response->assertSeeTextInOrder(['Dépôts à valider', '2']);
        $response->assertSeeText('2 dépôt(s) de clients en attente');
    }

    public function test_validation_page_says_when_nothing_is_pending(): void
    {
        Vetement::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.depot.vetements.index', ['moderation' => 'en_attente']))
            ->assertSeeText('Aucun dépôt en attente : tous les dépôts des clients ont été traités.');
    }

    public function test_quick_approval_from_the_list_returns_to_the_list(): void
    {
        $vetement = Vetement::factory()->enAttente()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.depot.vetements.approuver', $vetement), ['retour' => 'liste'])
            ->assertRedirect(route('admin.depot.vetements.index', ['moderation' => 'en_attente']));

        $this->assertSame(Moderation::Approuve, $vetement->fresh()->moderation);
    }
}
