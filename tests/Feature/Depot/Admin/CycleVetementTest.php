<?php

namespace Tests\Feature\Depot\Admin;

use App\Enums\Ateliers\TypeVetement;
use App\Enums\Depot\StatutVetement;
use App\Enums\Dons\EtatGeneral;
use App\Enums\Dons\TypeArticle;
use App\Enums\Upcycling\StatutProjet;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\DemandeReparation;
use App\Models\Don;
use App\Models\ProjetUpcycling;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CycleVetementTest extends TestCase
{
    use RefreshDatabase;

    public function test_clothing_is_sent_to_a_workshop_with_an_automatic_diagnosis(): void
    {
        $atelier = Atelier::factory()->create(['actif' => true, 'nom' => 'Fil d\'Or']);
        $vetement = Vetement::factory()->for(Categorie::factory()->create(['nom' => 'Pantalons & jeans']))
            ->create(['titre' => 'Jean troué', 'description' => 'Un trou au genou.']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.depot.vetements.reparer', $vetement), ['atelier_id' => $atelier->id]);

        $demande = DemandeReparation::query()->sole();
        $response->assertRedirect(route('admin.ateliers.demandes.show', $demande));
        $this->assertSame($vetement->id, $demande->vetement_id);
        $this->assertSame($vetement->user_id, $demande->user_id);
        $this->assertSame(TypeVetement::Pantalon, $demande->type_vetement);
        $this->assertSame('15.00', $demande->cout_estime);
        $this->assertTrue($vetement->demandesReparation()->exists());
    }

    public function test_clothing_is_turned_into_a_draft_upcycling_project_with_a_copy_of_its_photo(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('veste.jpg')->store('vetements', 'public');
        $vetement = Vetement::factory()->create(['titre' => 'Veste en jean', 'photo' => $photo]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.depot.vetements.transformer', $vetement));

        $projet = ProjetUpcycling::query()->sole();
        $response->assertRedirect(route('admin.upcycling.projets.edit', $projet));
        $this->assertSame(StatutProjet::Brouillon, $projet->statut);
        $this->assertSame($vetement->id, $projet->vetement_id);
        $this->assertSame(StatutVetement::Recycle, $vetement->fresh()->statut);
        $this->assertNotSame($photo, $projet->photo_avant);
        Storage::disk('public')->assertExists([$photo, $projet->photo_avant]);

        // Supprimer le projet ne supprime pas la photo du vêtement.
        $projet->delete();
        Storage::disk('public')->assertExists($photo);
    }

    public function test_clothing_is_given_to_an_association(): void
    {
        $association = Association::factory()->create(['active' => true, 'nom' => 'Croissant Rouge']);
        $vetement = Vetement::factory()->create(['genre' => 'femme', 'etat' => 'use']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.depot.vetements.donner', $vetement), ['association_id' => $association->id])
            ->assertRedirect(route('admin.depot.vetements.show', $vetement));

        $don = Don::query()->sole();
        $this->assertSame($vetement->id, $don->vetement_id);
        $this->assertSame(TypeArticle::VetementsFemme, $don->type_article);
        $this->assertSame(EtatGeneral::Moyen, $don->etat_general);
        $this->assertSame(StatutVetement::Donne, $vetement->fresh()->statut);
    }

    public function test_reserved_clothing_cannot_leave_the_catalogue_cycle(): void
    {
        $reservation = Reservation::factory()->create();
        $association = Association::factory()->create(['active' => true]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.depot.vetements.donner', $reservation->vetement), ['association_id' => $association->id])
            ->assertSessionHasErrors(['vetement' => 'Ce vêtement est réservé par un client : annulez d\'abord la réservation.']);

        $this->assertDatabaseCount('dons', 0);
    }

    public function test_clothing_page_shows_its_whole_cycle(): void
    {
        $vetement = Vetement::factory()->create();
        DemandeReparation::factory()->create(['vetement_id' => $vetement->id, 'atelier_id' => Atelier::factory()->create(['nom' => 'Fil d\'Or'])]);
        Don::factory()->create(['vetement_id' => $vetement->id, 'association_id' => Association::factory()->create(['nom' => 'Croissant Rouge'])]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.vetements.show', $vetement))
            ->assertSeeText(['Cycle du vêtement', 'Réparation chez Fil d\'Or', 'Don à Croissant Rouge']);
    }

    public function test_deleting_the_clothing_keeps_the_history_of_other_modules(): void
    {
        $vetement = Vetement::factory()->create();
        $don = Don::factory()->create(['vetement_id' => $vetement->id]);

        $vetement->delete();

        $this->assertNull($don->fresh()->vetement_id);
    }

    public function test_clients_cannot_use_the_cycle_actions(): void
    {
        $vetement = Vetement::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('admin.depot.vetements.transformer', $vetement))->assertForbidden();
        $this->assertDatabaseCount('projets_upcycling', 0);
    }
}
