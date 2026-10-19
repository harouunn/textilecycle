<?php

namespace App\Services\Depot;

use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Models\Reservation;
use App\Models\Vetement;

/**
 * Indicateurs du module pour les tableaux de bord, calculés en une requête SQL par table
 * (sommes conditionnelles) plutôt qu'avec un count() par indicateur.
 */
class StatistiquesDepot
{
    /**
     * @return array{total: int, a_valider: int, en_ligne: int, reserves: int, donnes: int}
     */
    public function vetements(): array
    {
        $ligne = Vetement::query()->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when moderation = ? then 1 else 0 end) as a_valider', [Moderation::EnAttente->value])
            ->selectRaw('sum(case when moderation = ? and statut = ? then 1 else 0 end) as en_ligne', [Moderation::Approuve->value, StatutVetement::Disponible->value])
            ->selectRaw('sum(case when statut = ? then 1 else 0 end) as reserves', [StatutVetement::Reserve->value])
            ->selectRaw('sum(case when statut = ? then 1 else 0 end) as donnes', [StatutVetement::Donne->value])
            ->first();

        return array_map('intval', (array) $ligne);
    }

    /**
     * @return array{total: int, en_attente: int, confirmee: int, retiree: int, annulee: int}
     */
    public function reservations(): array
    {
        $parStatut = Reservation::query()->toBase()
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $resultat = ['total' => (int) $parStatut->sum()];
        foreach (StatutReservation::cases() as $statut) {
            $resultat[$statut->value] = (int) ($parStatut[$statut->value] ?? 0);
        }

        return $resultat;
    }
}
