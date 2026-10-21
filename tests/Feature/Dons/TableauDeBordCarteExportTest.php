<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Exports\Dons\DonsExport;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TableauDeBordCarteExportTest extends TestCase
{
    use RefreshDatabase;

    // Tableau de bord

    public function test_dashboard_shows_kpis_and_caches_them(): void
    {
        $association = Association::factory()->create(['nom' => 'Croissant Rouge']);
        Don::factory()->statut(DonStatut::Recu)->create(['association_id' => $association->id, 'poids_kg' => 12.5]);
        Don::factory()->statut(DonStatut::Accepte)->create(['association_id' => $association->id]);
        Don::factory()->statut(DonStatut::Accepte)->create(['association_id' => $association->id]);
        Don::factory()->statut(DonStatut::Refuse)->create(['association_id' => $association->id]);
        Association::factory()->inactive()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dons.tableau-de-bord'))
            ->assertOk()
            ->assertSeeText(['12,5 kg', '75,0 %', 'Kilos reçus par mois', 'Top 5 des associations'])
            ->assertSee('graphique-kg-mois', false)
            ->assertSee('vendor/dons/chart.umd.min.js', false);

        $this->assertTrue(Cache::has('dons.tableau-de-bord'));
        $this->assertSame(1, Cache::get('dons.tableau-de-bord')['kpis']['associations_actives']);
        $this->assertSame(['Croissant Rouge'], Cache::get('dons.tableau-de-bord')['topAssociations']['labels']);
    }

    public function test_dashboard_is_reserved_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dons.tableau-de-bord'))->assertForbidden();
    }

    // Export

    public function test_admin_exports_the_filtered_list_in_excel_and_csv(): void
    {
        Excel::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dons.dons.export', ['format' => 'xlsx', 'statut' => 'recu']))->assertOk();
        Excel::assertDownloaded('dons-'.now()->format('Y-m-d').'.xlsx', function (DonsExport $export) {
            return $export->query()->toRawSql() === Don::query()->with(['association', 'user', 'campagne'])->filtrer(['statut' => 'recu'])->latest()->toRawSql();
        });

        $this->actingAs($admin)->get(route('admin.dons.dons.export', ['format' => 'csv']))->assertOk();
        Excel::assertDownloaded('dons-'.now()->format('Y-m-d').'.csv');
    }

    public function test_csv_contains_only_the_filtered_donations(): void
    {
        Don::factory()->statut(DonStatut::Recu)->create(['user_id' => User::factory()->create(['name' => 'Amel Recu'])->id]);
        Don::factory()->statut(DonStatut::Propose)->create(['user_id' => User::factory()->create(['name' => 'Karim Propose'])->id]);

        $reponse = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dons.dons.export', ['format' => 'csv', 'statut' => 'recu']));

        $reponse->assertOk()->assertDownload();
        $contenu = file_get_contents($reponse->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('Référence', $contenu);
        $this->assertStringContainsString('Amel Recu', $contenu);
        $this->assertStringNotContainsString('Karim Propose', $contenu);
    }

    public function test_xlsx_file_is_a_real_spreadsheet(): void
    {
        Don::factory()->count(3)->create();

        $reponse = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dons.dons.export', ['format' => 'xlsx']));

        $reponse->assertOk()->assertDownload('dons-'.now()->format('Y-m-d').'.xlsx');
        // Un fichier .xlsx est une archive zip.
        $this->assertStringStartsWith('PK', file_get_contents($reponse->baseResponse->getFile()->getPathname()));
    }

    public function test_export_rules(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dons.dons.export', ['format' => 'csv']))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/dons/dons/export/pdf')->assertNotFound();
    }

    // Carte et « près de moi »

    public function test_haversine_scope_keeps_nearby_associations_sorted_by_distance(): void
    {
        $centre = Association::factory()->create(['nom' => 'Tunis centre', 'latitude' => 36.8008, 'longitude' => 10.1800]);
        $lafayette = Association::factory()->create(['nom' => 'Lafayette', 'latitude' => 36.8139, 'longitude' => 10.1806]);
        $benArous = Association::factory()->create(['nom' => 'Ben Arous', 'latitude' => 36.7531, 'longitude' => 10.2189]);
        Association::factory()->create(['nom' => 'Sfax', 'latitude' => 34.7406, 'longitude' => 10.7603]);
        Association::factory()->create(['nom' => 'Sans coordonnées', 'latitude' => null, 'longitude' => null]);

        $proches = Association::query()->procheDe(36.8008, 10.1800, 10)->get();

        $this->assertSame(['Tunis centre', 'Lafayette', 'Ben Arous'], $proches->pluck('nom')->all());
        $this->assertEqualsWithDelta(0.0, (float) $proches[0]->distance_km, 0.01);
        $this->assertEqualsWithDelta(1.46, (float) $proches[1]->distance_km, 0.05);
        $this->assertEqualsWithDelta(6.4, (float) $proches[2]->distance_km, 0.2);

        $this->assertSame(['Tunis centre', 'Lafayette'], Association::query()->procheDe(36.8008, 10.1800, 3)->pluck('nom')->all());
    }

    public function test_associations_page_shows_the_map_and_filters_near_me(): void
    {
        Association::factory()->create(['nom' => 'Proche de moi', 'latitude' => 36.8139, 'longitude' => 10.1806]);
        Association::factory()->create(['nom' => 'Loin de moi', 'latitude' => 34.7406, 'longitude' => 10.7603]);

        $this->get(route('dons.index'))
            ->assertOk()
            ->assertSee('carte-associations', false)
            ->assertSee('vendor/dons/leaflet/leaflet.js', false)
            ->assertSee('Loin de moi', false)
            ->assertSeeText('Près de moi');

        $this->get(route('dons.index', ['lat' => 36.8008, 'lng' => 10.1800, 'rayon' => 10]))
            ->assertOk()
            ->assertSeeText(['Proche de moi', 'à 1,5 km', 'moins de 10 km'])
            ->assertDontSeeText('Faire un don à Loin de moi');

        // Position invalide : ignorée, pas de redirection.
        $this->get(route('dons.index', ['lat' => 500, 'lng' => 10]))->assertOk()->assertSeeText('La position reçue est invalide');
    }

    public function test_site_pages_may_ask_for_geolocation(): void
    {
        $this->get(route('dons.index'))->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
    }
}
