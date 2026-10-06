<?php

namespace Tests\Feature\Ateliers\Admin;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Ateliers\TypeReparation;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemandeReparationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.ateliers.demandes.index'))->assertRedirect(route('login'));
    }

    public function test_index_shows_requests_with_their_workshop_and_client(): void
    {
        $demande = DemandeReparation::factory()
            ->for(Atelier::factory()->create(['nom' => 'Fil d\'Or']))
            ->for(User::factory()->create(['name' => 'Sami Ben Ali']))
            ->create(['titre' => 'Manteau décousu']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.ateliers.demandes.index'));

        $response->assertOk();
        $response->assertSeeTextInOrder(['Manteau décousu', 'Fil d\'Or', 'Sami Ben Ali', $demande->cout_affiche]);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function filtres(): array
    {
        return [
            'recherche par titre' => ['search', 'Robe'],
            'statut' => ['statut', 'Robe'],
            'atelier' => ['atelier', 'Robe'],
        ];
    }

    #[DataProvider('filtres')]
    public function test_index_filters_requests(string $filtre, string $attendu): void
    {
        $atelier = Atelier::factory()->create();
        DemandeReparation::factory()->for($atelier)->statut(StatutDemande::Refusee)->create(['titre' => 'Robe déchirée']);
        DemandeReparation::factory()->statut(StatutDemande::EnAttente)->create(['titre' => 'Chemise sans bouton']);

        $valeur = ['search' => 'Robe', 'statut' => 'refusee', 'atelier' => $atelier->id][$filtre];
        $response = $this->actingAs(User::factory()->create())->get(route('admin.ateliers.demandes.index', [$filtre => $valeur]));

        $response->assertSeeText('Robe déchirée');
        $response->assertDontSeeText('Chemise sans bouton');
    }

    public function test_create_and_edit_forms_are_displayed(): void
    {
        $admin = User::factory()->create();
        $demande = DemandeReparation::factory()->create(['titre' => 'Manteau décousu']);

        $this->actingAs($admin)->get(route('admin.ateliers.demandes.create'))
            ->assertOk()
            ->assertSeeText('Nouvelle demande de réparation');
        $this->actingAs($admin)->get(route('admin.ateliers.demandes.edit', $demande))
            ->assertOk()
            ->assertSeeText('Modifier « Manteau décousu »');
    }

    public function test_admin_creates_a_request_which_is_diagnosed(): void
    {
        $atelier = Atelier::factory()->create();
        $client = User::factory()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('admin.ateliers.demandes.store'), [
            'atelier_id' => $atelier->id,
            'user_id' => $client->id,
            'titre' => 'Robe trop longue',
            'type_vetement' => 'robe_jupe',
            'description' => 'Ourlet à raccourcir de 5 cm.',
        ]);

        $demande = DemandeReparation::query()->sole();
        $response->assertRedirect(route('admin.ateliers.demandes.show', $demande));
        $this->assertSame($client->id, $demande->user_id);
        $this->assertEquals([TypeReparation::Ourlet], $demande->reparations->all());
        $this->assertSame('14.40', $demande->cout_estime);
    }

    public function test_admin_create_requires_workshop_and_client(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.ateliers.demandes.store'), [
            'titre' => 'Robe trop longue',
            'type_vetement' => 'robe_jupe',
            'description' => 'Ourlet.',
        ]);

        $response->assertSessionHasErrors([
            'atelier_id' => 'Le champ atelier est obligatoire.',
            'user_id' => 'Le champ client est obligatoire.',
        ]);
    }

    public function test_show_displays_the_diagnostic_and_the_processing_form(): void
    {
        $demande = DemandeReparation::factory()->create(['description' => 'La fermeture éclair est bloquée.']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.ateliers.demandes.show', $demande));

        $response->assertOk();
        $response->assertSeeText('Réparation(s) identifiée(s) : fermeture éclair.');
        $response->assertSeeText('Traiter la demande');
    }

    public function test_update_recomputes_the_diagnostic_and_keeps_the_photo(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')->store('reparations', 'public');
        $demande = DemandeReparation::factory()->create(['description' => 'Il manque un bouton.', 'photo' => $photo]);

        $response = $this->actingAs(User::factory()->create())->put(route('admin.ateliers.demandes.update', $demande), [
            'atelier_id' => $demande->atelier_id,
            'user_id' => $demande->user_id,
            'titre' => $demande->titre,
            'type_vetement' => 'haut',
            'description' => '',
        ]);

        $response->assertRedirect(route('admin.ateliers.demandes.show', $demande));
        $demande->refresh();
        $this->assertSame($photo, $demande->photo);
        $this->assertEquals([TypeReparation::AExpertiser], $demande->reparations->all());
        Storage::disk('public')->assertExists($photo);
    }

    public function test_workshop_accepts_a_request_with_final_cost_and_date(): void
    {
        $demande = DemandeReparation::factory()->create();

        $response = $this->actingAs(User::factory()->create())->patch(route('admin.ateliers.demandes.traiter', $demande), [
            'statut' => 'acceptee',
            'cout_final' => '27.50',
            'date_prevue' => '2026-10-20',
            'reponse_atelier' => 'Déposez le vêtement à l\'atelier.',
        ]);

        $response->assertRedirect(route('admin.ateliers.demandes.show', $demande));
        $response->assertSessionHas('success', 'La demande est maintenant « Acceptée ».');
        $demande->refresh();
        $this->assertSame(StatutDemande::Acceptee, $demande->statut);
        $this->assertSame('27.50', $demande->cout_final);
        $this->assertSame('27,50 DT', $demande->cout_affiche);
        $this->assertSame('2026-10-20', $demande->date_prevue->format('Y-m-d'));
    }

    public function test_refusal_requires_a_message_to_the_client(): void
    {
        $demande = DemandeReparation::factory()->create();

        $response = $this->actingAs(User::factory()->create())->patch(route('admin.ateliers.demandes.traiter', $demande), [
            'statut' => 'refusee',
            'cout_final' => '-3',
        ]);

        $response->assertSessionHasErrors([
            'reponse_atelier' => 'Expliquez au client pourquoi la demande est refusée.',
            'cout_final' => 'Le champ coût final doit être supérieur ou égal à 0.',
        ]);
        $this->assertSame(StatutDemande::EnAttente, $demande->fresh()->statut);
    }

    public function test_admin_deletes_a_request(): void
    {
        $demande = DemandeReparation::factory()->create();

        $response = $this->actingAs(User::factory()->create())->delete(route('admin.ateliers.demandes.destroy', $demande));

        $response->assertRedirect(route('admin.ateliers.demandes.index'));
        $this->assertModelMissing($demande);
    }

    public function test_deleting_a_workshop_deletes_its_requests(): void
    {
        $demande = DemandeReparation::factory()->create();

        $this->actingAs(User::factory()->create())->delete(route('admin.ateliers.destroy', $demande->atelier));

        $this->assertModelMissing($demande);
    }
}
