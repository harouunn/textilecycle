<?php

namespace Tests\Feature\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogueFiltresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function filtres(): array
    {
        return [
            'recherche' => [['search' => 'lin']],
            'genre' => [['genre' => 'femme']],
            'taille' => [['taille' => 'S']],
            'état' => [['etat' => 'neuf']],
        ];
    }

    /**
     * @param  array<string, string>  $filtre
     */
    #[DataProvider('filtres')]
    public function test_catalogue_filters_clothes(array $filtre): void
    {
        Vetement::factory()->create(['titre' => 'Robe en lin', 'genre' => Genre::Femme, 'taille' => Taille::S, 'etat' => Etat::Neuf]);
        Vetement::factory()->create(['titre' => 'Blouson cuir', 'genre' => Genre::Homme, 'taille' => Taille::XL, 'etat' => Etat::Use]);

        $response = $this->get(route('depot.catalogue.index', $filtre));

        $response->assertSeeText('Robe en lin');
        $response->assertDontSeeText('Blouson cuir');
    }

    public function test_catalogue_can_be_sorted_alphabetically(): void
    {
        Vetement::factory()->create(['titre' => 'Zèbre imprimé', 'date_depot' => today()]);
        Vetement::factory()->create(['titre' => 'Anorak bleu', 'date_depot' => today()->subMonth()]);

        $this->get(route('depot.catalogue.index'))->assertSeeTextInOrder(['Zèbre imprimé', 'Anorak bleu']);
        $this->get(route('depot.catalogue.index', ['tri' => 'titre']))->assertSeeTextInOrder(['Anorak bleu', 'Zèbre imprimé']);
    }

    public function test_categories_follow_the_admin_order_and_inactive_ones_are_hidden(): void
    {
        Categorie::factory()->create(['nom' => 'Zzz en premier', 'ordre' => 1]);
        Categorie::factory()->create(['nom' => 'Aaa en second', 'ordre' => 2]);
        $masquee = Categorie::factory()->inactive()->create(['nom' => 'Collection cachée']);
        Vetement::factory()->for($masquee)->create(['titre' => 'Vêtement caché']);

        $response = $this->get(route('depot.catalogue.index'));

        $response->assertSeeTextInOrder(['Zzz en premier', 'Aaa en second']);
        $response->assertDontSeeText('Collection cachée');
        $response->assertDontSeeText('Vêtement caché');
    }

    public function test_selected_category_shows_its_description_as_page_heading(): void
    {
        $categorie = Categorie::factory()->create(['nom' => 'Pulls', 'description' => 'Mailles chaudes pour l\'hiver.']);

        $this->get(route('depot.catalogue.index', ['categorie' => $categorie->id]))
            ->assertSeeTextInOrder(['Pulls', 'Mailles chaudes pour l\'hiver.']);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->get(route('depot.catalogue.index', ['taille' => 'XXXL', 'tri' => 'prix']))
            ->assertSessionHasErrors(['taille', 'tri']);
    }
}
