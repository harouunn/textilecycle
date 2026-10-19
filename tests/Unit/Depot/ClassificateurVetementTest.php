<?php

namespace Tests\Unit\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use App\Services\Depot\AnalyseImage;
use App\Services\Depot\ClassificateurVetement;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClassificateurVetementTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: ?int, 2: ?string, 3: Etat, 4: Genre}>
     */
    public static function titres(): array
    {
        return [
            'chemise en lin' => ['Chemise en lin pour homme', 1, 'Lin', Etat::Bon, Genre::Homme],
            'jean, accents' => ['Jean délavé', 2, 'Denim', Etat::Use, Genre::Unisexe],
            'robe neuve' => ['Robe en soie neuve avec étiquette', 3, 'Soie', Etat::Neuf, Genre::Femme],
            'comme neuf avant neuf' => ['Pull en laine comme neuf', 4, 'Laine', Etat::TresBon, Genre::Unisexe],
            'à réparer' => ['Manteau enfant troué', 5, null, Etat::AReparer, Genre::Enfant],
            'premier type cité' => ['Robe chemise', 3, null, Etat::Bon, Genre::Femme],
            'lin ≠ linge' => ['Linge de maison', null, null, Etat::Bon, Genre::Unisexe],
        ];
    }

    #[DataProvider('titres')]
    public function test_title_gives_category_material_state_and_gender(string $titre, ?int $categorie, ?string $matiere, Etat $etat, Genre $genre): void
    {
        $resultat = $this->classer($titre);

        $this->assertSame($categorie, $resultat['categorie_id']);
        $this->assertSame($matiere, $resultat['matiere']);
        $this->assertSame($etat, $resultat['etat']);
        $this->assertSame($genre, $resultat['genre']);
    }

    public function test_size_is_read_from_the_title(): void
    {
        $this->assertSame(Taille::XL, $this->classer('Veste taille XL')['taille']);
        $this->assertNull($this->classer("L'ourlet d'une veste")['taille']);
    }

    public function test_blue_trousers_are_probably_denim(): void
    {
        $resultat = $this->classer('Pantalon droit', couleur: 'bleu');

        $this->assertSame('Denim', $resultat['matiere']);
        $this->assertContains('Pantalon bleu sur la photo : matière probable Denim, à vérifier.', $resultat['indices']);
    }

    public function test_description_is_generated_from_photo_and_title(): void
    {
        $resultat = $this->classer('Chemise en lin homme taille M très bon état', couleur: 'bleu');

        $this->assertSame('Chemise en lin, coloris bleu, uni. Très bon état, peu porté. Coupe homme. Taille M.', $resultat['description']);
    }

    public function test_without_title_only_the_photo_is_used(): void
    {
        $resultat = $this->classer(null, couleur: 'rouge', uni: false);

        $this->assertNull($resultat['categorie_id']);
        $this->assertSame('Vêtement, coloris rouge, à motifs. Bon état général. Coupe unisexe.', $resultat['description']);
        $this->assertContains('Aucun type de vêtement reconnu dans le titre : choisissez la catégorie.', $resultat['indices']);
    }

    /**
     * @return array<string, mixed>
     */
    private function classer(?string $titre, string $couleur = 'gris', bool $uni = true): array
    {
        $analyse = $this->createStub(AnalyseImage::class);
        $analyse->method('analyser')->willReturn(['couleur' => $couleur, 'part' => 80, 'uni' => $uni]);

        return (new ClassificateurVetement($analyse))->classer('photo.jpg', $titre, $this->categories());
    }

    /**
     * Les catégories du DepotSeeder.
     *
     * @return Collection<int, Categorie>
     */
    private function categories(): Collection
    {
        return collect([
            [1, 'Hauts', 'T-shirts, chemises, blouses et tops.'],
            [2, 'Pantalons & jeans', 'Pantalons, jeans, shorts et leggings.'],
            [3, 'Robes & jupes', 'Robes, jupes et combinaisons.'],
            [4, 'Pulls & gilets', 'Pulls, sweats, gilets et cardigans.'],
            [5, 'Vestes & manteaux', 'Vestes, blousons, manteaux et imperméables.'],
            [6, 'Enfants', 'Vêtements pour bébés et enfants.'],
        ])->map(function (array $ligne) {
            $categorie = new Categorie(['nom' => $ligne[1], 'description' => $ligne[2]]);
            $categorie->id = $ligne[0];

            return $categorie;
        });
    }
}
