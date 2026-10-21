<?php

namespace App\Services\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\TypeArticle;
use App\Models\Association;
use App\Models\Don;
use App\Models\DonHistorique;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Indicateurs du tableau de bord « Associations & dons » (back office), mis en cache 10 minutes.
 */
class StatistiquesDons
{
    /**
     * @return array{kpis: array<string, float|int|null>, kgParMois: array{labels: list<string>, data: list<float>}, parType: array{labels: list<string>, data: list<int>}, topAssociations: array{labels: list<string>, data: list<float>}}
     */
    public function tableauDeBord(): array
    {
        return Cache::remember('dons.tableau-de-bord', config('textilecycle.dons.cache_ttl'), fn () => [
            'kpis' => $this->kpis(),
            'kgParMois' => $this->kgParMois(),
            'parType' => $this->parType(),
            'topAssociations' => $this->topAssociations(),
        ]);
    }

    /**
     * @return array{total_kg: float, dons_ce_mois: int, taux_acceptation: ?float, associations_actives: int}
     */
    private function kpis(): array
    {
        $acceptes = Don::query()->whereIn('statut', [DonStatut::Accepte, DonStatut::Planifie, DonStatut::Recu, DonStatut::Distribue])->count();
        $refuses = Don::query()->where('statut', DonStatut::Refuse)->count();

        return [
            'total_kg' => round((float) Don::query()->recus()->sum('poids_kg'), 1),
            'dons_ce_mois' => Don::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            // Parmi les dons sur lesquels l'association a tranché (les dons en attente ou annulés ne comptent pas).
            'taux_acceptation' => $acceptes + $refuses > 0 ? round($acceptes / ($acceptes + $refuses) * 100, 1) : null,
            'associations_actives' => Association::query()->active()->count(),
        ];
    }

    /**
     * Kilos reçus par mois sur les 12 derniers mois, d'après la date de réception (historique).
     * Le regroupement par mois est fait en PHP : la requête reste la même sur MySQL et SQLite.
     */
    private function kgParMois(): array
    {
        $debut = now()->startOfMonth()->subMonths(11);
        $mois = collect(range(0, 11))->mapWithKeys(fn (int $i) => [$debut->copy()->addMonths($i)->format('Y-m') => 0.0])->all();

        $receptions = DonHistorique::query()
            ->where('nouveau_statut', DonStatut::Recu)
            ->where('don_historiques.created_at', '>=', $debut)
            ->join('dons', 'dons.id', '=', 'don_historiques.don_id')
            ->whereNull('dons.deleted_at')
            ->get(['don_historiques.created_at', 'dons.poids_kg']);

        foreach ($receptions as $reception) {
            $cle = Carbon::parse($reception->created_at)->format('Y-m');
            if (isset($mois[$cle])) {
                $mois[$cle] += (float) $reception->poids_kg;
            }
        }

        return [
            'labels' => array_map(fn (string $m) => Carbon::createFromFormat('!Y-m', $m)->locale('fr')->translatedFormat('M Y'), array_keys($mois)),
            'data' => array_map(fn (float $kg) => round($kg, 1), array_values($mois)),
        ];
    }

    private function parType(): array
    {
        $comptes = Don::query()->selectRaw('type_article, count(*) as total')->groupBy('type_article')->pluck('total', 'type_article');

        return [
            'labels' => collect(TypeArticle::cases())->map->label()->all(),
            'data' => collect(TypeArticle::cases())->map(fn (TypeArticle $type) => (int) ($comptes[$type->value] ?? 0))->all(),
        ];
    }

    private function topAssociations(): array
    {
        $top = Association::query()
            ->withSum(['dons as kg_recus' => fn ($q) => $q->recus()], 'poids_kg')
            ->orderByDesc('kg_recus')
            ->limit(5)
            ->get()
            ->filter(fn (Association $a) => (float) $a->kg_recus > 0);

        return [
            'labels' => $top->pluck('nom')->values()->all(),
            'data' => $top->map(fn (Association $a) => round((float) $a->kg_recus, 1))->values()->all(),
        ];
    }
}
