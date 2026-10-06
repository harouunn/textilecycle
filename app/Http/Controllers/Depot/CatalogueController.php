<?php

namespace App\Http\Controllers\Depot;

use App\Enums\Depot\Moderation;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    /**
     * Catalogue public : vêtements disponibles, filtrables par catégorie.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'categorie' => ['nullable', 'integer'],
        ]);

        $categories = Categorie::query()
            ->withCount(['vetements' => fn ($query) => $query->auCatalogue()])
            ->orderBy('nom')
            ->get();

        $vetements = Vetement::query()
            ->with('categorie')
            ->auCatalogue()
            ->filter($filters)
            ->latest('date_depot')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('depot.catalogue.index', [
            'vetements' => $vetements,
            'categories' => $categories,
            'categorieActive' => isset($filters['categorie']) ? (int) $filters['categorie'] : null,
        ]);
    }

    /**
     * Fiche détaillée d'un vêtement. Un vêtement pas encore approuvé n'est visible que par son déposant.
     */
    public function show(Request $request, Vetement $vetement): View
    {
        abort_unless($vetement->moderation === Moderation::Approuve || $request->user()?->id === $vetement->user_id, 404);

        $vetement->load('categorie');

        $similaires = Vetement::query()
            ->with('categorie')
            ->auCatalogue()
            ->where('categorie_id', $vetement->categorie_id)
            ->whereKeyNot($vetement->id)
            ->latest('date_depot')
            ->limit(4)
            ->get();

        return view('depot.catalogue.show', compact('vetement', 'similaires'));
    }
}
