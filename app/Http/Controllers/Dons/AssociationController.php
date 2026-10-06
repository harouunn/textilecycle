<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Models\Association;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Front office: browse the active partner associations.
 */
class AssociationController extends Controller
{
    public function index(Request $request): View
    {
        $ville = trim((string) $request->query('ville'));

        $associations = Association::active()
            ->when($ville !== '', fn ($query) => $query->where('ville', $ville))
            ->orderBy('nom')
            ->paginate(9)
            ->withQueryString();

        $villes = Association::active()->distinct()->orderBy('ville')->pluck('ville');

        return view('dons.associations.index', compact('associations', 'villes', 'ville'));
    }

    public function show(Association $association): View
    {
        abort_unless($association->active, 404);

        $association->loadCount(['dons as dons_recus_count' => fn ($query) => $query->where('statut', 'recu')]);

        return view('dons.associations.show', compact('association'));
    }
}
