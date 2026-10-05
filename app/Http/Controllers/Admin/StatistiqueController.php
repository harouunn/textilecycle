<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Application-wide statistics. Each module section is computed only when its tables exist,
 * so the page keeps working while the other teams' modules are not merged yet.
 */
class StatistiqueController extends Controller
{
    /** Depot module: vetements.statut values (see the module's migration). */
    private const VETEMENT_STATUTS = [
        'disponible' => ['Disponible', 'primary'],
        'reserve' => ['Réservé', 'warning'],
        'donne' => ['Donné', 'success'],
        'recycle' => ['Recyclé', 'secondary'],
    ];

    private const VETEMENT_ETATS = [
        'neuf' => 'Neuf',
        'tres_bon' => 'Très bon état',
        'bon' => 'Bon état',
        'use' => 'Usé',
        'a_reparer' => 'À réparer',
    ];

    public function index(): View
    {
        $mois = $this->derniersMois(6);
        $depot = $this->depot();
        $dons = $this->dons();

        $activite = $mois->map(fn (array $m) => [
            'label' => $m['label'],
            'inscriptions' => User::whereBetween('created_at', [$m['debut'], $m['fin']])->count(),
            'depots' => $depot ? DB::table('vetements')->whereBetween('created_at', [$m['debut'], $m['fin']])->count() : null,
            'dons' => Don::whereBetween('created_at', [$m['debut'], $m['fin']])->count(),
        ]);

        $vueEnsemble = [
            'users' => User::count(),
            'vetements' => $depot['total'] ?? null,
            'dons' => $dons['totals']['dons'],
            'kg_redistribues' => $dons['totals']['poids_recu'],
            'actifs' => $this->utilisateursActifs($depot !== null),
        ];

        $modules = [
            'depot' => ['titre' => 'Dépôt & vêtements', 'icone' => 'bx-package', 'installe' => $depot !== null],
            'ateliers' => ['titre' => 'Ateliers & réparations', 'icone' => 'bx-wrench', 'installe' => false],
            'upcycling' => ['titre' => 'Upcycling', 'icone' => 'bx-palette', 'installe' => false],
            'dons' => ['titre' => 'Associations & dons', 'icone' => 'bx-donate-heart', 'installe' => true],
        ];

        return view('admin.statistiques.index', compact('vueEnsemble', 'activite', 'modules', 'depot', 'dons'));
    }

    /**
     * Depot module statistics, or null when its tables are not in the database.
     */
    private function depot(): ?array
    {
        if (! Schema::hasTable('vetements')) {
            return null;
        }

        $parStatut = DB::table('vetements')->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $parEtat = DB::table('vetements')->selectRaw('etat, count(*) as total')->groupBy('etat')->pluck('total', 'etat');

        $parCategorie = Schema::hasTable('categories')
            ? DB::table('categories')
                ->leftJoin('vetements', 'vetements.categorie_id', '=', 'categories.id')
                ->selectRaw('categories.nom as nom, count(vetements.id) as total')
                ->groupBy('categories.id', 'categories.nom')
                ->orderByDesc('total')->limit(8)->get()
            : collect();

        return [
            'total' => DB::table('vetements')->count(),
            'deposants' => DB::table('vetements')->distinct()->count('user_id'),
            'categories' => Schema::hasTable('categories') ? DB::table('categories')->count() : 0,
            'parStatut' => collect(self::VETEMENT_STATUTS)->map(fn ($def, $statut) => [
                'label' => $def[0], 'color' => $def[1], 'total' => (int) ($parStatut[$statut] ?? 0),
            ]),
            'parEtat' => collect(self::VETEMENT_ETATS)->map(fn ($label, $etat) => [
                'label' => $label, 'total' => (int) ($parEtat[$etat] ?? 0),
            ]),
            'parCategorie' => $parCategorie,
        ];
    }

    private function dons(): array
    {
        $parStatut = Don::selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $parType = Don::selectRaw('type_article, count(*) as total, coalesce(sum(quantite), 0) as articles')
            ->groupBy('type_article')->get()->keyBy('type_article');

        return [
            'totals' => [
                'associations' => Association::count(),
                'associations_actives' => Association::active()->count(),
                'dons' => Don::count(),
                'poids' => (float) Don::sum('poids_kg'),
                'poids_recu' => (float) Don::where('statut', 'recu')->sum('poids_kg'),
                'articles_recus' => (int) Don::where('statut', 'recu')->sum('quantite'),
            ],
            'parStatut' => collect(Don::STATUTS)->map(fn ($label, $statut) => [
                'label' => $label,
                'color' => ['danger' => 'error'][Don::STATUT_COLORS[$statut]] ?? Don::STATUT_COLORS[$statut],
                'total' => (int) ($parStatut[$statut] ?? 0),
            ]),
            'parType' => collect(Don::TYPES)->map(fn ($label, $type) => [
                'label' => $label,
                'total' => (int) ($parType[$type]->total ?? 0),
                'articles' => (int) ($parType[$type]->articles ?? 0),
            ])->sortByDesc('articles'),
            'parAssociation' => Association::withCount('dons')->withSum('dons', 'poids_kg')
                ->orderByDesc('dons_sum_poids_kg')->take(8)->get(),
            'topDonateurs' => User::query()
                ->addSelect([
                    'dons_count' => Don::selectRaw('count(*)')->whereColumn('user_id', 'users.id'),
                    'dons_sum_quantite' => Don::selectRaw('coalesce(sum(quantite), 0)')->whereColumn('user_id', 'users.id'),
                ])
                ->orderByDesc('dons_count')->orderBy('name')->take(5)->get()
                ->filter(fn ($user) => $user->dons_count > 0),
        ];
    }

    /**
     * Users who deposited at least one garment or proposed at least one donation.
     */
    private function utilisateursActifs(bool $avecDepot): int
    {
        return User::query()
            ->where(function ($query) use ($avecDepot) {
                $query->whereExists(fn ($q) => $q->selectRaw('1')->from('dons')->whereColumn('dons.user_id', 'users.id'));
                if ($avecDepot) {
                    $query->orWhereExists(fn ($q) => $q->selectRaw('1')->from('vetements')->whereColumn('vetements.user_id', 'users.id'));
                }
            })
            ->count();
    }

    /**
     * The last $n months (oldest first) with their bounds and a French label.
     */
    private function derniersMois(int $n): Collection
    {
        $debut = Carbon::now()->startOfMonth()->subMonths($n - 1);

        return collect(range(0, $n - 1))->map(function ($i) use ($debut) {
            $m = $debut->copy()->addMonths($i);

            return [
                'label' => ucfirst($m->locale('fr')->translatedFormat('M Y')),
                'debut' => $m->copy(),
                'fin' => $m->copy()->endOfMonth(),
            ];
        });
    }
}
