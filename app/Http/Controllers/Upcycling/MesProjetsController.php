<?php

namespace App\Http\Controllers\Upcycling;

use App\Enums\Upcycling\Difficulte;
use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\ProjetUpcyclingRequest;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\EnregistrerAvecPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Projets proposés par l'utilisateur connecté.
 * Un projet proposé est créé en brouillon : il est publié depuis le back office.
 */
class MesProjetsController extends Controller
{
    public function __construct(private readonly EnregistrerAvecPhotos $enregistrement) {}

    public function index(Request $request): View
    {
        $projets = ProjetUpcycling::query()
            ->whereBelongsTo($request->user())
            ->withCount('etapes')
            ->latest()
            ->paginate(10);

        return view('upcycling.mes-projets', compact('projets'));
    }

    public function create(): View
    {
        return view('upcycling.create', [
            'projet' => new ProjetUpcycling,
            'difficultes' => Difficulte::options(),
        ]);
    }

    public function store(ProjetUpcyclingRequest $request): RedirectResponse
    {
        $projet = new ProjetUpcycling;
        $this->enregistrement->enregistrer($projet, [...$request->donnees(), 'user_id' => $request->user()->id], $request, ['photo_avant', 'photo_apres'], 'upcycling/projets');

        return redirect()
            ->route('upcycling.projets.etapes.index', $projet)
            ->with('success', 'Merci ! Votre projet a été enregistré. Ajoutez maintenant les étapes de réalisation.');
    }

    public function edit(ProjetUpcycling $projet): View
    {
        Gate::authorize('update', $projet);

        return view('upcycling.edit', [
            'projet' => $projet,
            'difficultes' => Difficulte::options(),
        ]);
    }

    public function update(ProjetUpcyclingRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        Gate::authorize('update', $projet);

        $this->enregistrement->enregistrer($projet, $request->donnees($projet), $request, ['photo_avant', 'photo_apres'], 'upcycling/projets');

        return redirect()
            ->route('upcycling.mes-projets')
            ->with('success', 'Votre projet « '.$projet->titre.' » a été mis à jour.');
    }

    public function destroy(ProjetUpcycling $projet): RedirectResponse
    {
        Gate::authorize('delete', $projet);

        $projet->delete();

        return redirect()
            ->route('upcycling.mes-projets')
            ->with('success', 'Le projet « '.$projet->titre.' » a été supprimé.');
    }
}
