<?php

namespace App\Http\Controllers\Admin\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\EtapeProjetRequest;
use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\EnregistrerAvecPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Étapes d'un projet : l'ajout et la liste se font depuis la page du projet
 * (admin.upcycling.projets.show), la modification sur une page dédiée.
 */
class EtapeProjetController extends Controller
{
    public function __construct(private readonly EnregistrerAvecPhotos $enregistrement) {}

    public function store(EtapeProjetRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        $etape = new EtapeProjet(['projet_upcycling_id' => $projet->id]);
        $this->enregistrement->enregistrer($etape, $request->donnees(), $request, ['photo'], 'upcycling/etapes');

        return redirect()
            ->route('admin.upcycling.projets.show', $projet)
            ->with('success', 'L\'étape n°'.$etape->numero.' a été ajoutée.');
    }

    public function edit(ProjetUpcycling $projet, EtapeProjet $etape): View
    {
        $this->verifierAppartenance($projet, $etape);

        return view('admin.upcycling.etapes.edit', compact('projet', 'etape'));
    }

    public function update(EtapeProjetRequest $request, ProjetUpcycling $projet, EtapeProjet $etape): RedirectResponse
    {
        $this->verifierAppartenance($projet, $etape);

        $this->enregistrement->enregistrer($etape, $request->donnees($etape), $request, ['photo'], 'upcycling/etapes');

        return redirect()
            ->route('admin.upcycling.projets.show', $projet)
            ->with('success', 'L\'étape n°'.$etape->numero.' a été mise à jour.');
    }

    public function destroy(ProjetUpcycling $projet, EtapeProjet $etape): RedirectResponse
    {
        $this->verifierAppartenance($projet, $etape);

        $etape->delete();

        return redirect()
            ->route('admin.upcycling.projets.show', $projet)
            ->with('success', 'L\'étape n°'.$etape->numero.' a été supprimée.');
    }

    private function verifierAppartenance(ProjetUpcycling $projet, EtapeProjet $etape): void
    {
        abort_unless($etape->projet_upcycling_id === $projet->id, 404);
    }
}
