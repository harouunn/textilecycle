<?php

namespace App\Http\Controllers\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutVetement;
use App\Enums\Depot\Taille;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Vetement;
use App\Services\Depot\ImpactEcologique;
use App\Services\Depot\Recommandations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    /**
     * Catalogue public : vêtements approuvés et disponibles, avec recherche, filtres et tri.
     */
    public function index(Request $request, ImpactEcologique $impact): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'integer'],
            'genre' => ['nullable', Rule::enum(Genre::class)],
            'taille' => ['nullable', Rule::enum(Taille::class)],
            'etat' => ['nullable', Rule::enum(Etat::class)],
            'tri' => ['nullable', Rule::in(['recents', 'anciens', 'titre'])],
        ]);

        $categories = Categorie::query()
            ->actives()
            ->withCount(['vetements' => fn ($query) => $query->auCatalogue()])
            ->get();

        $vetements = Vetement::query()
            ->with('categorie')
            ->auCatalogue()
            ->whereIn('categorie_id', $categories->modelKeys())
            ->filter($filters)
            ->trier($filters['tri'] ?? null)
            ->paginate(12)
            ->withQueryString();

        return view('depot.catalogue.index', [
            'vetements' => $vetements,
            'categories' => $categories,
            // Total recalculé au plus toutes les 10 minutes.
            'impactGlobal' => Cache::remember('depot.impact-global', now()->addMinutes(10), fn () => $impact->global()),
            'filters' => $filters,
            'categorieActive' => isset($filters['categorie']) ? (int) $filters['categorie'] : null,
        ]);
    }

    /**
     * Fiche détaillée d'un vêtement. Un vêtement pas encore approuvé n'est visible que par son déposant.
     */
    public function show(Request $request, Vetement $vetement, Recommandations $recommandations, ImpactEcologique $impact): View
    {
        abort_unless($vetement->moderation === Moderation::Approuve || $request->user()?->id === $vetement->user_id, 404);

        $vetement->load('categorie');

        return view('depot.catalogue.show', [
            'vetement' => $vetement,
            'similaires' => $recommandations->pour($vetement),
            'impact' => $impact->pourVetement($vetement),
            'reservable' => $vetement->moderation === Moderation::Approuve
                && $vetement->statut === StatutVetement::Disponible
                && $request->user()?->id !== $vetement->user_id,
        ]);
    }
}
