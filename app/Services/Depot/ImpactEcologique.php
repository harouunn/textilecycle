<?php

namespace App\Services\Depot;

use App\Enums\Depot\StatutVetement;
use App\Models\Vetement;
use Illuminate\Support\Str;

/**
 * Impact écologique d'un vêtement réutilisé plutôt que produit neuf : eau et CO₂ évités.
 * Valeurs indicatives par type de vêtement (ordres de grandeur publiés pour la fabrication textile,
 * ex. ~2 700 L d'eau pour un t-shirt en coton, ~7 500 L pour un jean), arrondies volontairement.
 */
class ImpactEcologique
{
    /** Type reconnu dans la catégorie ou le titre => [litres d'eau, kg de CO₂]. */
    private const REPERES = [
        'jean' => [7500, 25],
        'manteau' => [4000, 30],
        'veste' => [4000, 25],
        'blouson' => [4000, 25],
        'robe' => [3000, 12],
        'pull' => [3000, 15],
        'gilet' => [3000, 15],
        'pantalon' => [5000, 18],
        'jupe' => [2500, 9],
        'chemise' => [2700, 7],
        'enfant' => [1000, 3],
    ];

    private const PAR_DEFAUT = [2700, 6];

    /**
     * @return array{eau: int, co2: int}
     */
    public function pourVetement(Vetement $vetement): array
    {
        $texte = Str::lower(Str::ascii($vetement->titre.' '.$vetement->categorie?->nom));

        foreach (self::REPERES as $mot => [$eau, $co2]) {
            if (str_contains($texte, $mot)) {
                return ['eau' => $eau, 'co2' => $co2];
            }
        }

        return ['eau' => self::PAR_DEFAUT[0], 'co2' => self::PAR_DEFAUT[1]];
    }

    /**
     * Impact cumulé des vêtements qui ont trouvé une seconde vie (donnés ou recyclés), plus leur nombre.
     *
     * @return array{eau: int, co2: int, vetements: int}
     */
    public function global(): array
    {
        $total = ['eau' => 0, 'co2' => 0, 'vetements' => 0];

        Vetement::query()->with('categorie:id,nom')
            ->whereIn('statut', [StatutVetement::Donne, StatutVetement::Recycle])
            ->select(['id', 'titre', 'categorie_id'])
            ->lazy(500)
            ->each(function (Vetement $vetement) use (&$total) {
                $impact = $this->pourVetement($vetement);
                $total['eau'] += $impact['eau'];
                $total['co2'] += $impact['co2'];
                $total['vetements']++;
            });

        return $total;
    }

    public static function formaterEau(int $litres): string
    {
        return $litres >= 1_000_000
            ? number_format($litres / 1_000_000, 1, ',', ' ').' millions de litres'
            : number_format($litres, 0, ',', ' ').' litres';
    }
}
