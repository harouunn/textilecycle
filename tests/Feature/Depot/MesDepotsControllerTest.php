<?php

namespace Tests\Feature\Depot;

use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutVetement;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MesDepotsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('depot.mes-depots.index'))->assertRedirect(route('login'));
        $this->get(route('depot.mes-depots.create'))->assertRedirect(route('login'));
    }

    public function test_user_deposits_clothing_with_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $autre = User::factory()->create();

        $response = $this->actingAs($user)->post(route('depot.mes-depots.store'), [
            ...$this->payload(),
            'photo' => UploadedFile::fake()->create('jean.jpg', 100, 'image/jpeg'),
            'user_id' => $autre->id,
            'statut' => 'donne',
        ]);

        $response->assertRedirect(route('depot.mes-depots.index'));
        $response->assertSessionHas('success', 'Merci ! Votre vêtement « Jean droit » a bien été déposé. Il apparaîtra dans le catalogue après validation.');
        $vetement = Vetement::query()->sole();
        $this->assertSame($user->id, $vetement->user_id);
        $this->assertSame(StatutVetement::Disponible, $vetement->statut);
        $this->assertSame(Moderation::EnAttente, $vetement->moderation);
        Storage::disk('public')->assertExists($vetement->photo);
    }

    public function test_invalid_deposit_is_rejected_with_french_messages(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('depot.mes-depots.store'), [
            ...$this->payload(),
            'taille' => 'XXXL',
            'date_depot' => now()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors([
            'taille' => 'La valeur sélectionnée pour taille est invalide.',
            'date_depot' => 'La date de dépôt ne peut pas être dans le futur.',
        ]);
        $this->assertDatabaseCount('vetements', 0);
    }

    public function test_index_lists_only_the_users_own_clothes(): void
    {
        $user = User::factory()->create();
        Vetement::factory()->for($user)->create(['titre' => 'Mon blouson']);
        Vetement::factory()->create(['titre' => 'Blouson du voisin']);

        $response = $this->actingAs($user)->get(route('depot.mes-depots.index'));

        $response->assertSeeText('Mon blouson');
        $response->assertDontSeeText('Blouson du voisin');
    }

    public function test_owner_updates_their_clothing(): void
    {
        $user = User::factory()->create();
        $vetement = Vetement::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('depot.mes-depots.update', $vetement), $this->payload());

        $response->assertRedirect(route('depot.mes-depots.index'));
        $this->assertSame('Jean droit', $vetement->fresh()->titre);
    }

    public function test_user_cannot_edit_update_or_delete_another_users_clothing(): void
    {
        $vetement = Vetement::factory()->create(['titre' => 'Pas à moi']);
        $intrus = User::factory()->create();

        $this->actingAs($intrus)->get(route('depot.mes-depots.edit', $vetement))->assertForbidden();
        $this->actingAs($intrus)->put(route('depot.mes-depots.update', $vetement), $this->payload())->assertForbidden();
        $this->actingAs($intrus)->delete(route('depot.mes-depots.destroy', $vetement))->assertForbidden();

        $this->assertSame('Pas à moi', $vetement->fresh()->titre);
    }

    public function test_owner_deletes_their_clothing_and_its_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')->store('vetements', 'public');
        $vetement = Vetement::factory()->for($user)->create(['photo' => $photo]);

        $response = $this->actingAs($user)->delete(route('depot.mes-depots.destroy', $vetement));

        $response->assertRedirect(route('depot.mes-depots.index'));
        $this->assertModelMissing($vetement);
        Storage::disk('public')->assertMissing($photo);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'categorie_id' => Categorie::factory()->create()->id,
            'titre' => 'Jean droit',
            'description' => 'Jean bleu brut, porté quelques fois.',
            'taille' => 'L',
            'genre' => 'unisexe',
            'matiere' => 'Denim',
            'etat' => 'bon',
            'date_depot' => now()->format('Y-m-d'),
        ];
    }
}
