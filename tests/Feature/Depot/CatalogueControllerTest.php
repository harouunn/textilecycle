<?php

namespace Tests\Feature\Depot;

use App\Enums\Depot\StatutVetement;
use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_shows_only_available_clothes(): void
    {
        Vetement::factory()->create(['titre' => 'Robe disponible']);
        Vetement::factory()->statut(StatutVetement::Donne)->create(['titre' => 'Robe déjà donnée']);

        $response = $this->get(route('depot.catalogue.index'));

        $response->assertSeeText('Robe disponible');
        $response->assertDontSeeText('Robe déjà donnée');
    }

    public function test_catalogue_filters_by_category(): void
    {
        $enfants = Categorie::factory()->create(['nom' => 'Enfants']);
        Vetement::factory()->for($enfants)->create(['titre' => 'Salopette enfant']);
        Vetement::factory()->create(['titre' => 'Costume homme']);

        $response = $this->get(route('depot.catalogue.index', ['categorie' => $enfants->id]));

        $response->assertSeeText('Salopette enfant');
        $response->assertDontSeeText('Costume homme');
    }

    public function test_details_page_shows_the_clothing(): void
    {
        $vetement = Vetement::factory()->for(Categorie::factory()->create(['nom' => 'Pulls']))->create([
            'titre' => 'Pull irlandais',
            'matiere' => 'Laine',
        ]);

        $response = $this->get(route('depot.catalogue.show', $vetement));

        $response->assertSeeText('Pull irlandais');
        $response->assertSeeText('Pulls');
        $response->assertSeeText('Laine');
    }
}
