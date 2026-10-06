<?php

namespace App\Http\Controllers\Upcycling;

use App\Enums\Upcycling\Difficulte;
use App\Http\Controllers\Controller;
use App\Models\ProjetUpcycling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Galerie publique des projets publiés. */
class GalerieController extends Controller
{
    public function index(Request $request): View
    {
        $difficulte = Difficulte::tryFrom((string) $request->query('difficulte'));

        $projets = ProjetUpcycling::query()
            ->publie()
            ->with('user')
            ->withCount('etapes')
            ->when($difficulte, fn ($query) => $query->where('difficulte', $difficulte))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('upcycling.index', [
            'projets' => $projets,
            'difficultes' => Difficulte::options(),
            'difficulteActive' => $difficulte?->value,
        ]);
    }

    public function show(ProjetUpcycling $projet): View
    {
        // Un brouillon n'est visible que par son auteur (aperçu)
        Gate::authorize('view', $projet);

        $projet->load(['user', 'etapes']);

        return view('upcycling.show', compact('projet'));
    }
}
