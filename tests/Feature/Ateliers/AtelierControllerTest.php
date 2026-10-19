<?php

namespace Tests\Feature\Ateliers;

use App\Models\Atelier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtelierControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_active_workshops(): void
    {
        Atelier::factory()->create(['nom' => 'Fil d\'Or', 'actif' => true]);
        Atelier::factory()->create(['nom' => 'Atelier fermé', 'actif' => false]);

        $response = $this->get(route('ateliers.index'));

        $response->assertOk();
        $response->assertSeeText('Fil d\'Or');
        $response->assertDontSeeText('Atelier fermé');
    }

    public function test_index_filters_by_city(): void
    {
        Atelier::factory()->create(['nom' => 'Couture Tunis', 'ville' => 'Tunis', 'actif' => true]);
        Atelier::factory()->create(['nom' => 'Couture Sfax', 'ville' => 'Sfax', 'actif' => true]);

        $response = $this->get(route('ateliers.index', ['ville' => 'Sfax']));

        $response->assertSeeText('Couture Sfax');
        $response->assertDontSeeText('Couture Tunis');
    }

    public function test_show_displays_an_active_workshop_and_its_price_list(): void
    {
        $atelier = Atelier::factory()->create(['nom' => 'Fil d\'Or', 'actif' => true]);

        $response = $this->get(route('ateliers.show', $atelier));

        $response->assertOk();
        $response->assertSeeText('Fil d\'Or');
        $response->assertSeeText('Fermeture éclair');
        $response->assertSeeText('Connectez-vous pour demander une réparation');
    }

    public function test_inactive_workshop_is_not_found(): void
    {
        $atelier = Atelier::factory()->create(['actif' => false]);

        $this->get(route('ateliers.show', $atelier))->assertNotFound();
    }
}
