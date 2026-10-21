<?php

namespace App\Services\Dons;

use App\Models\Don;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Impact environnemental des dons : CO₂ et eau évités par kilo de textile réutilisé.
 * Les coefficients (et leur source) sont dans config/textilecycle.php, clé dons.impact.
 * Seuls les dons reçus par une association comptent (Reçu, Distribué).
 */
class ImpactCalculator
{
    /**
     * @return array{kg: float, co2_kg: float, eau_litres: float}
     */
    public function pourKg(float $kg): array
    {
        return [
            'kg' => round($kg, 2),
            'co2_kg' => round($kg * (float) config('textilecycle.dons.impact.co2_kg_par_kg'), 1),
            'eau_litres' => round($kg * (float) config('textilecycle.dons.impact.eau_litres_par_kg')),
        ];
    }

    /**
     * Impact du don s'il est (ou une fois qu'il sera) reçu ; « estime » indique qu'il ne l'est pas encore.
     *
     * @return array{kg: float, co2_kg: float, eau_litres: float, estime: bool}
     */
    public function pourDon(Don $don): array
    {
        return [...$this->pourKg((float) $don->poids_kg), 'estime' => ! $don->estRecu()];
    }

    /**
     * @return array{kg: float, co2_kg: float, eau_litres: float, dons: int}
     */
    public function pourUtilisateur(User $user): array
    {
        $totaux = Don::query()->whereBelongsTo($user)->recus()
            ->selectRaw('count(*) as dons, coalesce(sum(poids_kg), 0) as kg')
            ->first();

        return [...$this->pourKg((float) $totaux->kg), 'dons' => (int) $totaux->dons];
    }

    /**
     * Impact de tous les dons reçus, mis en cache (affiché sur la page d'accueil du module).
     *
     * @return array{kg: float, co2_kg: float, eau_litres: float, dons: int}
     */
    public function global(): array
    {
        return Cache::remember('dons.impact.global', config('textilecycle.dons.cache_ttl'), function () {
            $totaux = Don::query()->recus()->selectRaw('count(*) as dons, coalesce(sum(poids_kg), 0) as kg')->first();

            return [...$this->pourKg((float) $totaux->kg), 'dons' => (int) $totaux->dons];
        });
    }
}
