<?php

namespace Tests\Feature;

use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutReservation;
use App\Models\Association;
use App\Models\DemandeReparation;
use App\Models\ProjetUpcycling;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use App\Services\Depot\DetecteurDoublons;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DonneesDemonstrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_fills_every_module_with_photos(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        // Dépôt : chaque vêtement a sa photo, sa miniature et son empreinte.
        $this->assertSame(28, Vetement::count());
        Vetement::all()->each(function (Vetement $vetement) {
            Storage::disk('public')->assertExists([$vetement->photo, Vetement::cheminMiniature($vetement->photo)]);
            $this->assertNotNull($vetement->photo_empreinte);
        });
        $this->assertSame(3, Vetement::where('moderation', Moderation::EnAttente)->count());
        $this->assertSame(1, Vetement::where('moderation', Moderation::Refuse)->count());

        // Réservations à toutes les étapes, dont une annulée automatiquement (expirée).
        foreach (StatutReservation::cases() as $statut) {
            $this->assertTrue(Reservation::where('statut', $statut)->exists(), "Aucune réservation « {$statut->value} »");
        }
        $this->assertTrue(Reservation::where('motif_annulation', 'like', 'Expirée%')->exists());

        // L'alerte doublon a un exemple à montrer.
        $doublon = Vetement::where('titre', 'like', 'Jean clair%')->sole();
        $this->assertNotEmpty(app(DetecteurDoublons::class)->doublonsDe($doublon));

        // Les autres gestions ont des photos et sont reliées aux vêtements du catalogue.
        $this->assertSame(0, DemandeReparation::whereNull('photo')->count());
        $this->assertSame(0, ProjetUpcycling::whereNull('photo_avant')->count());
        $this->assertSame(0, Association::whereNull('logo')->count());
        $this->assertTrue(DemandeReparation::whereNotNull('vetement_id')->exists());
        $this->assertTrue(ProjetUpcycling::whereNotNull('vetement_id')->exists());

        $this->assertTrue(User::where('email', 'test@example.com')->sole()->isAdmin());
    }

    public function test_admin_without_association_can_open_the_collection_slots_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dons.espace.creneaux.index'))
            ->assertOk()
            ->assertSeeText('Vous n\'êtes responsable d\'aucune association');
    }
}
