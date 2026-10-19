<?php

namespace Tests\Feature;

use App\Enums\Depot\StatutVetement;
use App\Models\Association;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccueilEtTableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_cycle_with_real_figures_and_latest_clothes(): void
    {
        Vetement::factory(2)->create();
        Vetement::factory()->create(['titre' => 'Veste en lin récente']);
        Association::factory()->create(['nom' => 'Croissant Rouge', 'active' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeText(['Quatre chemins pour une seconde vie', '3', 'vêtement(s) disponible(s)', 'Veste en lin récente', 'Croissant Rouge']);
        $response->assertSee(route('depot.catalogue.index'));
        $response->assertSee(route('ateliers.index'));
        $response->assertSee(route('upcycling.index'));
        $response->assertSee(route('dons.index'));
    }

    public function test_admin_dashboard_shows_real_totals_and_tasks_to_handle(): void
    {
        Vetement::factory()->enAttente()->create();
        Vetement::factory()->statut(StatutVetement::Donne)->create(['titre' => 'Jean donné']);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeTextInOrder(['Vêtements déposés', '2']);
        $response->assertSeeTextInOrder(['À traiter', '1', 'Dépôts à valider']);
        $response->assertSeeText('7 500 litres');
    }
}
