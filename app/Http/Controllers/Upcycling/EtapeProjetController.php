<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\EtapeProjetRequest;
use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\EnregistrerAvecPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Étapes des projets de l'utilisateur connecté (liste + ajout sur upcycling.projets.etapes.index). */
class EtapeProjetController extends Controller
{
    public function __construct(private readonly EnregistrerAvecPhotos $enregistrement) {}

    public function index(ProjetUpcycling $projet): View
    {
        Gate::authorize('update', $projet);

        $projet->load('etapes');

        return view('upcycling.etapes.index', [
            'projet' => $projet,
            'prochainNumero' => $projet->prochainNumeroEtape(),
        ]);
    }

    public function store(EtapeProjetRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        Gate::authorize('update', $projet);

        $etape = new EtapeProjet(['projet_upcycling_id' => $projet->id]);
        $this->enregistrement->enregistrer($etape, $request->donnees(), $request, ['photo'], 'upcycling/etapes');

        return redirect()
            ->route('upcycling.projets.etapes.index', $projet)
            ->with('success', 'L\'étape n°'.$etape->numero.' a été ajoutée.');
    }

    public function edit(ProjetUpcycling $projet, EtapeProjet $etape): View
    {
        $this->autoriser($projet, $etape);

        return view('upcycling.etapes.edit', compact('projet', 'etape'));
    }

    public function update(EtapeProjetRequest $request, ProjetUpcycling $projet, EtapeProjet $etape): RedirectResponse
    {
        $this->autoriser($projet, $etape);

        $this->enregistrement->enregistrer($etape, $request->donnees($etape), $request, ['photo'], 'upcycling/etapes');

        return redirect()
            ->route('upcycling.projets.etapes.index', $projet)
            ->with('success', 'L\'étape n°'.$etape->numero.' a été mise à jour.');
    }

    public function destroy(ProjetUpcycling $projet, EtapeProjet $etape): RedirectResponse
    {
        $this->autoriser($projet, $etape);

        $etape->delete();

        return redirect()
            ->route('upcycling.projets.etapes.index', $projet)
            ->with('success', 'L\'étape n°'.$etape->numero.' a été supprimée.');
    }

    private function autoriser(ProjetUpcycling $projet, EtapeProjet $etape): void
    {
        Gate::authorize('update', $projet);
        abort_unless($etape->projet_upcycling_id === $projet->id, 404);
    }
}
