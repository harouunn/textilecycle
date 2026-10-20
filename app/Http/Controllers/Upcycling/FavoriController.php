<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Models\ProjetUpcycling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FavoriController extends Controller
{
    public function index(Request $request): View
    {
        $projets = $request->user()->projetsFavoris()
            ->publie()
            ->orderByPivot('created_at', 'desc')
            ->orderByDesc('projets_upcycling.id')
            ->paginate(9);

        return view('upcycling.mes-favoris', compact('projets'));
    }

    public function store(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        // Même verrou que la modération pour vérifier le statut au moment de l'ajout.
        DB::transaction(function () use ($request, $projet) {
            $projet = ProjetUpcycling::query()->lockForUpdate()->findOrFail($projet->id);
            abort_unless($projet->estPublie(), 403, 'Seuls les projets publiés peuvent être ajoutés aux favoris.');
            $request->user()->projetsFavoris()->syncWithoutDetaching([$projet->id]);
        });

        return back()->with('success', 'Projet ajouté à vos favoris.');
    }

    public function destroy(Request $request, ProjetUpcycling $projet): RedirectResponse
    {
        // L'identité vient de la session, jamais des données du formulaire.
        $request->user()->projetsFavoris()->detach($projet->id);

        return back()->with('success', 'Projet retiré de vos favoris.');
    }
}
