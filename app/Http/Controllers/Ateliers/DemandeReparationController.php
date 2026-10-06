<?php

namespace App\Http\Controllers\Ateliers;

use App\Enums\Ateliers\TypeVetement;
use App\Http\Controllers\Ateliers\Concerns\GerePhotoDemande;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ateliers\StoreDemandeReparationRequest;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * « Demander une réparation » et « Mes demandes » : l'utilisateur connecté ne voit que ses propres demandes.
 */
class DemandeReparationController extends Controller
{
    use GerePhotoDemande;

    public function index(Request $request): View
    {
        $demandes = DemandeReparation::query()
            ->with('atelier')
            ->whereBelongsTo($request->user())
            ->latest()
            ->latest('id')
            ->paginate(10);

        return view('ateliers.demandes.index', compact('demandes'));
    }

    public function create(Atelier $atelier): View
    {
        abort_unless($atelier->actif, 404);

        return view('ateliers.demandes.create', [
            'atelier' => $atelier,
            'demande' => new DemandeReparation(['type_vetement' => TypeVetement::Haut]),
        ]);
    }

    public function store(StoreDemandeReparationRequest $request, Atelier $atelier): RedirectResponse
    {
        abort_unless($atelier->actif, 404);

        $demande = new DemandeReparation([
            ...$this->avecPhoto($request, $request->validated()),
            'atelier_id' => $atelier->id,
            'user_id' => $request->user()->id,
        ]);
        $demande->diagnostiquer()->save();

        return redirect()
            ->route('ateliers.demandes.index')
            ->with('success', "Votre demande « {$demande->titre} » a été envoyée à {$atelier->nom}. "
                ."Estimation : {$demande->cout_affiche}, environ {$demande->delai_estime_jours} jour(s).");
    }

    public function destroy(DemandeReparation $demande): RedirectResponse
    {
        Gate::authorize('delete', $demande);

        $demande->deletePhoto();
        $demande->delete();

        return redirect()
            ->route('ateliers.demandes.index')
            ->with('success', "Votre demande « {$demande->titre} » a été annulée.");
    }
}
