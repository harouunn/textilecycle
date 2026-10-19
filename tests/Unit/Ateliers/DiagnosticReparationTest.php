<?php

namespace Tests\Unit\Ateliers;

use App\Enums\Ateliers\TypeReparation;
use App\Enums\Ateliers\TypeVetement;
use App\Services\Ateliers\DiagnosticReparation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DiagnosticReparationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: TypeVetement, 2: list<TypeReparation>, 3: float, 4: int}>
     */
    public static function descriptions(): array
    {
        return [
            'un bouton' => ['Il manque un bouton.', TypeVetement::Haut, [TypeReparation::Bouton], 5.0, 1],
            'accents et majuscules' => ['La COUTURE est DÉCOUSUE.', TypeVetement::Haut, [TypeReparation::Couture], 10.0, 2],
            'deux réparations' => ['Jean troué au genou et fermeture éclair cassée', TypeVetement::Pantalon, [TypeReparation::Trou, TypeReparation::Fermeture], 33.0, 4],
            'robe majorée' => ['Ourlet à raccourcir', TypeVetement::RobeJupe, [TypeReparation::Ourlet], 14.4, 2],
            'dommage important' => ['Plusieurs trous sur le devant', TypeVetement::Haut, [TypeReparation::Trou], 19.5, 3],
            'matière délicate' => ['Veste en cuir, doublure déchirée', TypeVetement::VesteManteau, [TypeReparation::Trou, TypeReparation::Doublure], 101.25, 8],
            'début de mot seulement' => ["J'ai l'impression qu'il est à la limite de l'usure", TypeVetement::Haut, [TypeReparation::AExpertiser], 20.0, 5],
        ];
    }

    /**
     * @param  list<TypeReparation>  $reparations
     */
    #[DataProvider('descriptions')]
    public function test_it_recognises_repairs_and_estimates_cost_and_delay(string $description, TypeVetement $vetement, array $reparations, float $cout, int $delai): void
    {
        $resultat = (new DiagnosticReparation)->analyser($description, false, $vetement);

        $this->assertSame($reparations, $resultat['reparations']);
        $this->assertSame($cout, $resultat['cout_estime']);
        $this->assertSame($delai, $resultat['delai_estime_jours']);
    }

    public function test_photo_without_description_gives_a_provisional_estimate(): void
    {
        $resultat = (new DiagnosticReparation)->analyser(null, true, TypeVetement::Autre);

        $this->assertSame([TypeReparation::AExpertiser], $resultat['reparations']);
        $this->assertSame(20.0, $resultat['cout_estime']);
        $this->assertSame(5, $resultat['delai_estime_jours']);
        $this->assertStringContainsString('examinera la photo', $resultat['diagnostic']);
    }

    public function test_busy_workshop_adds_one_day_per_five_pending_requests(): void
    {
        $diagnostic = new DiagnosticReparation;

        $libre = $diagnostic->analyser('Un bouton à recoudre', false, TypeVetement::Haut, 4);
        $charge = $diagnostic->analyser('Un bouton à recoudre', false, TypeVetement::Haut, 12);

        $this->assertSame(1, $libre['delai_estime_jours']);
        $this->assertSame(3, $charge['delai_estime_jours']);
        $this->assertStringContainsString('traite déjà 12 demande(s)', $charge['diagnostic']);
    }

    public function test_explanation_lists_the_identified_repairs(): void
    {
        $resultat = (new DiagnosticReparation)->analyser('Zip bloqué', true, TypeVetement::Haut);

        $this->assertStringContainsString('Réparation(s) identifiée(s) : fermeture éclair.', $resultat['diagnostic']);
        $this->assertStringContainsString('La photo jointe', $resultat['diagnostic']);
    }
}
