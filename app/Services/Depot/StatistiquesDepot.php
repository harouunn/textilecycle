<?php

namespace App\Services\Depot;

use App\Enums\Depot\Creneau;
use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    /**
     * Rapport complet du module (page « Statistiques du dépôt » et rapport PDF).
     *
     * @return array<string, mixed>
     */
    public function rapport(): array
    {
        $vetements = $this->vetements();
        $reservations = $this->reservations();
        $moderation = $this->compterPar(Vetement::query(), 'moderation');

        // Taux d'acceptation : parmi les dépôts déjà traités par l'admin (approuvés ou refusés).
        $traites = ($moderation['approuve'] ?? 0) + ($moderation['refuse'] ?? 0);
        // Taux de réussite : parmi les réservations terminées (retirées ou annulées).
        $terminees = $reservations['retiree'] + $reservations['annulee'];
        $expirees = Reservation::query()->where('statut', StatutReservation::Annulee)->where('motif_annulation', 'like', 'Expirée%')->count();

        return [
            'indicateurs' => [
                'total' => $vetements['total'],
                'ce_mois' => Vetement::query()->where('date_depot', '>=', now()->startOfMonth()->toDateString())->count(),
                'a_valider' => $vetements['a_valider'],
                'en_ligne' => $vetements['en_ligne'],
                'taux_acceptation' => $this->pourcentage($moderation['approuve'] ?? 0, $traites),
                'taux_reussite' => $this->pourcentage($reservations['retiree'], $terminees),
                'taux_expiration' => $this->pourcentage($expirees, $reservations['annulee']),
                'expirees' => $expirees,
            ],
            'vetements' => $vetements,
            'reservations' => $reservations,
            'moderation' => $moderation,
            'impact' => app(ImpactEcologique::class)->global(),
            'par_mois' => $this->parMois(),
            'par_categorie' => $this->parCategorie(),
            'par_etat' => $this->libelles($this->compterPar(Vetement::query(), 'etat'), Etat::cases()),
            'par_genre' => $this->libelles($this->compterPar(Vetement::query(), 'genre'), Genre::cases()),
            'par_taille' => $this->libelles($this->compterPar(Vetement::query(), 'taille'), Taille::cases()),
            'par_creneau' => $this->libelles($this->compterPar(Reservation::query(), 'creneau'), Creneau::cases()),
            'orientation' => [
                'Réparation (Ateliers)' => Vetement::query()->has('demandesReparation')->count(),
                'Upcycling' => Vetement::query()->has('projetsUpcycling')->count(),
                'Don à une association' => Vetement::query()->has('dons')->count(),
                'Retiré par un client' => $reservations['retiree'],
            ],
            'top_deposants' => User::query()
                ->withCount('vetements')
                ->has('vetements')
                ->orderByDesc('vetements_count')
                ->limit(5)
                ->get(['id', 'name', 'email']),
            'genere_le' => now(),
        ];
    }

    /**
     * Dépôts et réservations des 6 derniers mois (regroupés en PHP : même code sous MySQL et SQLite).
     *
     * @return list<array{mois: string, depots: int, reservations: int}>
     */
    private function parMois(): array
    {
        $debut = now()->startOfMonth()->subMonths(5);
        $depots = Vetement::query()->where('date_depot', '>=', $debut->toDateString())->pluck('date_depot')
            ->countBy(fn ($date) => $date->format('Y-m'));
        $reservations = Reservation::query()->where('created_at', '>=', $debut)->pluck('created_at')
            ->countBy(fn ($date) => $date->format('Y-m'));

        return collect(range(0, 5))->map(function (int $decalage) use ($debut, $depots, $reservations) {
            $mois = $debut->copy()->addMonths($decalage);

            return [
                'mois' => ucfirst($mois->locale('fr')->translatedFormat('M Y')),
                'depots' => (int) ($depots[$mois->format('Y-m')] ?? 0),
                'reservations' => (int) ($reservations[$mois->format('Y-m')] ?? 0),
            ];
        })->all();
    }

    /**
     * @return Collection<int, Categorie>
     */
    private function parCategorie(): Collection
    {
        return Categorie::query()
            ->orderBy('ordre')
            ->withCount([
                'vetements',
                'vetements as disponibles_count' => fn ($query) => $query->auCatalogue(),
                'vetements as reserves_count' => fn ($query) => $query->where('statut', StatutVetement::Reserve),
                'vetements as donnes_count' => fn ($query) => $query->where('statut', StatutVetement::Donne),
            ])
            ->get(['id', 'nom', 'couleur']);
    }

    /**
     * @return array<string, int>
     */
    private function compterPar(Builder $query, string $colonne): array
    {
        return $query->toBase()
            ->selectRaw("{$colonne} as valeur, count(*) as total")
            ->groupBy($colonne)
            ->pluck('total', 'valeur')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Remplace les valeurs brutes par les libellés des enums, dans l'ordre des enums, zéros compris.
     *
     * @param  array<string, int>  $comptes
     * @param  list<\BackedEnum>  $cas
     * @return array<string, int>
     */
    private function libelles(array $comptes, array $cas): array
    {
        return collect($cas)->mapWithKeys(fn ($enum) => [$enum->label() => $comptes[$enum->value] ?? 0])->all();
    }

    private function pourcentage(int $partie, int $total): ?int
    {
        return $total > 0 ? (int) round(100 * $partie / $total) : null;
    }
}
