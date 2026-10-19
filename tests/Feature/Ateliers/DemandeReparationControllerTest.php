<?php

namespace Tests\Feature\Ateliers;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Ateliers\TypeReparation;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemandeReparationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $atelier = Atelier::factory()->create(['actif' => true]);

        $this->get(route('ateliers.demandes.index'))->assertRedirect(route('login'));
        $this->get(route('ateliers.demandes.create', $atelier))->assertRedirect(route('login'));
        $this->post(route('ateliers.demandes.store', $atelier), $this->payload())->assertRedirect(route('login'));
    }

    public function test_create_shows_the_request_form_for_the_workshop(): void
    {
        $atelier = Atelier::factory()->create(['nom' => 'Fil d\'Or', 'actif' => true]);

        $response = $this->actingAs(User::factory()->create())->get(route('ateliers.demandes.create', $atelier));

        $response->assertOk();
        $response->assertSeeText('Fil d\'Or');
        $response->assertSeeText('Obtenir mon diagnostic');
    }

    public function test_user_requests_a_repair_from_a_description_and_gets_a_diagnostic(): void
    {
        $user = User::factory()->create();
        $atelier = Atelier::factory()->create(['nom' => 'Fil d\'Or', 'actif' => true]);

        $response = $this->actingAs($user)->post(route('ateliers.demandes.store', $atelier), [
            ...$this->payload(),
            'user_id' => User::factory()->create()->id,
            'statut' => 'terminee',
        ]);

        $response->assertRedirect(route('ateliers.demandes.index'));
        $response->assertSessionHas('success', 'Votre demande « Jean abîmé » a été envoyée à Fil d\'Or. Estimation : 33,00 DT, environ 4 jour(s).');

        $demande = DemandeReparation::query()->sole();
        $this->assertSame($user->id, $demande->user_id);
        $this->assertSame($atelier->id, $demande->atelier_id);
        $this->assertSame(StatutDemande::EnAttente, $demande->statut);
        $this->assertEquals([TypeReparation::Trou, TypeReparation::Fermeture], $demande->reparations->all());
        $this->assertSame('33.00', $demande->cout_estime);
        $this->assertSame(4, $demande->delai_estime_jours);
    }

    public function test_user_requests_a_repair_from_a_photo_only(): void
    {
        Storage::fake('public');
        $atelier = Atelier::factory()->create(['actif' => true]);

        $response = $this->actingAs(User::factory()->create())->post(route('ateliers.demandes.store', $atelier), [
            ...$this->payload(),
            'description' => '',
            'photo' => UploadedFile::fake()->create('veste.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect(route('ateliers.demandes.index'));
        $demande = DemandeReparation::query()->sole();
        Storage::disk('public')->assertExists($demande->photo);
        $this->assertEquals([TypeReparation::AExpertiser], $demande->reparations->all());
        $this->assertStringContainsString('examinera la photo', $demande->diagnostic);
    }

    public function test_request_needs_a_description_or_a_photo(): void
    {
        $atelier = Atelier::factory()->create(['actif' => true]);

        $response = $this->actingAs(User::factory()->create())->post(route('ateliers.demandes.store', $atelier), [
            ...$this->payload(),
            'description' => '',
            'type_vetement' => 'chaussure',
        ]);

        $response->assertSessionHasErrors([
            'description' => 'Décrivez le problème ou ajoutez une photo : le diagnostic a besoin de l\'un des deux.',
            'type_vetement' => 'La valeur sélectionnée pour type de vêtement est invalide.',
        ]);
        $this->assertDatabaseCount('demandes_reparation', 0);
    }

    public function test_inactive_workshop_cannot_receive_requests(): void
    {
        $atelier = Atelier::factory()->create(['actif' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('ateliers.demandes.create', $atelier))->assertNotFound();
        $this->actingAs($user)->post(route('ateliers.demandes.store', $atelier), $this->payload())->assertNotFound();
        $this->assertDatabaseCount('demandes_reparation', 0);
    }

    public function test_busy_workshop_gives_a_longer_delay(): void
    {
        $atelier = Atelier::factory()->create(['actif' => true]);
        DemandeReparation::factory(5)->for($atelier)->create();
        DemandeReparation::factory(3)->for($atelier)->statut(StatutDemande::Terminee)->create();

        $this->actingAs(User::factory()->create())->post(route('ateliers.demandes.store', $atelier), [
            ...$this->payload(),
            'description' => 'Un bouton à recoudre',
        ]);

        // 1 jour pour un bouton + 1 jour pour les 5 demandes encore ouvertes (les terminées ne comptent pas)
        $this->assertSame(2, DemandeReparation::query()->latest('id')->first()->delai_estime_jours);
    }

    public function test_index_lists_only_the_users_own_requests_with_their_diagnostic(): void
    {
        $user = User::factory()->create();
        DemandeReparation::factory()->for($user)->create(['titre' => 'Ma chemise', 'description' => 'Il manque un bouton.']);
        DemandeReparation::factory()->create(['titre' => 'Chemise du voisin']);

        $response = $this->actingAs($user)->get(route('ateliers.demandes.index'));

        $response->assertSeeText('Ma chemise');
        $response->assertSeeText('Bouton / pression');
        $response->assertDontSeeText('Chemise du voisin');
    }

    public function test_owner_cancels_a_pending_request_and_its_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photo = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')->store('reparations', 'public');
        $demande = DemandeReparation::factory()->for($user)->create(['photo' => $photo]);

        $response = $this->actingAs($user)->delete(route('ateliers.demandes.destroy', $demande));

        $response->assertRedirect(route('ateliers.demandes.index'));
        $this->assertModelMissing($demande);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_user_cannot_cancel_another_users_request_or_one_already_in_progress(): void
    {
        $user = User::factory()->create();
        $autre = DemandeReparation::factory()->create();
        $enCours = DemandeReparation::factory()->for($user)->statut(StatutDemande::EnCours)->create();

        $this->actingAs($user)->delete(route('ateliers.demandes.destroy', $autre))->assertForbidden();
        $this->actingAs($user)->delete(route('ateliers.demandes.destroy', $enCours))->assertForbidden();

        $this->assertModelExists($autre);
        $this->assertModelExists($enCours);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'titre' => 'Jean abîmé',
            'type_vetement' => 'pantalon',
            'description' => 'Jean troué au genou et fermeture éclair cassée.',
        ];
    }
}
