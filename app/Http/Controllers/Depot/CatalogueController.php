<?php

namespace App\Http\Controllers\Depot;

use App\Enums\Depot\StatutVetement;
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
            ->withCount(['vetements' => fn ($query) => $query->where('statut', StatutVetement::Disponible)])
            ->orderBy('nom')
            ->get();

        $vetements = Vetement::query()
            ->with('categorie')
            ->where('statut', StatutVetement::Disponible)
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
     * Fiche détaillée d'un vêtement.
     */
    public function show(Vetement $vetement): View
    {
        $vetement->load('categorie');

        $similaires = Vetement::query()
            ->with('categorie')
            ->where('statut', StatutVetement::Disponible)
            ->where('categorie_id', $vetement->categorie_id)
            ->whereKeyNot($vetement->id)
            ->latest('date_depot')
            ->limit(4)
            ->get();

        return view('depot.catalogue.show', compact('vetement', 'similaires'));
    }
}
