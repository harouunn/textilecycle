<?php

namespace Tests\Feature\Depot\Admin;

use App\Models\Categorie;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use App\Services\Depot\StatistiquesDepot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatistiquesDepotTest extends TestCase
{
    use RefreshDatabase;

    public function test_rates_are_computed_from_moderation_and_reservations(): void
    {
        Vetement::factory()->count(3)->create();
        Vetement::factory()->refuse()->create();
        Vetement::factory()->enAttente()->create();
        Reservation::factory()->retiree()->count(3)->create();
        Reservation::factory()->annulee()->create();
        Reservation::factory()->annulee('Expirée : le vêtement n\'a pas été retiré à la date prévue.')->create();

        $indicateurs = app(StatistiquesDepot::class)->rapport()['indicateurs'];

        // Les réservations créent aussi 5 vêtements approuvés : 8 approuvés sur 9 traités.
        $this->assertSame(89, $indicateurs['taux_acceptation']);
        $this->assertSame(60, $indicateurs['taux_reussite']);
        $this->assertSame(50, $indicateurs['taux_expiration']);
        $this->assertSame(1, $indicateurs['a_valider']);
    }

    public function test_rates_are_empty_without_data(): void
    {
        $indicateurs = app(StatistiquesDepot::class)->rapport()['indicateurs'];

        $this->assertNull($indicateurs['taux_acceptation']);
        $this->assertNull($indicateurs['taux_reussite']);
    }

    public function test_breakdowns_by_category_and_month(): void
    {
        $hauts = Categorie::factory()->create(['nom' => 'Hauts']);
        Vetement::factory()->count(2)->for($hauts)->create(['date_depot' => now()->toDateString(), 'etat' => 'neuf']);
        Vetement::factory()->for($hauts)->create(['date_depot' => now()->subMonths(8)->toDateString(), 'etat' => 'neuf']);

        $rapport = app(StatistiquesDepot::class)->rapport();

        $this->assertSame(3, $rapport['par_categorie']->firstWhere('nom', 'Hauts')->vetements_count);
        $this->assertSame(2, end($rapport['par_mois'])['depots']);
        $this->assertCount(6, $rapport['par_mois']);
        $this->assertSame(3, $rapport['par_etat']['Neuf']);
    }

    public function test_admin_sees_the_statistics_page_and_downloads_the_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        $deposant = User::factory()->create(['name' => 'Inès Martin']);
        Vetement::factory()->count(2)->for($deposant)->create();

        $this->actingAs($admin)->get(route('admin.depot.statistiques'))
            ->assertOk()
            ->assertSeeText('Statistiques du dépôt')
            ->assertSeeText('Taux d\'acceptation')
            ->assertSeeText('Inès Martin');

        $this->actingAs($admin)->get(route('admin.depot.statistiques.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_clients_cannot_see_the_statistics(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.depot.statistiques'))->assertForbidden();
    }
}
