<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\CreneauRequest;
use App\Models\Association;
use App\Models\CreneauCollecte;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace association : le responsable ouvre les créneaux de collecte à domicile de son association.
 */
class EspaceCreneauController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('accederEspace', Association::class);

        $associations = Association::gereesPar($request->user())->orderBy('nom')->get();
        $creneaux = CreneauCollecte::query()
            ->whereIn('association_id', $associations->modelKeys())
            ->whereDate('date', '>=', today())
            ->with('association')
            ->avecReservations()
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->paginate(20);

        return view('dons.espace.creneaux', compact('associations', 'creneaux'));
    }

    public function store(CreneauRequest $request): RedirectResponse
    {
        $association = $request->association();
        $this->authorize('gererCreneaux', $association);

        $creneau = $association->creneaux()->create($request->safe()->except('association_id'));

        return redirect()->route('dons.espace.creneaux.index')
            ->with('success', "Créneau du {$creneau->libelle()} ouvert ({$creneau->capacite_max} collecte(s)).");
    }

    public function destroy(CreneauCollecte $creneau): RedirectResponse
    {
        $this->authorize('delete', $creneau);

        $creneau->delete();

        return back()->with('success', 'Le créneau a été supprimé.');
    }
}
