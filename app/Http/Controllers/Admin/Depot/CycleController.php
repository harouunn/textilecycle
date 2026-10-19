<?php

namespace App\Http\Controllers\Admin\Depot;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\Vetement;
use App\Services\Depot\CycleVetement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Actions « Cycle du vêtement » de la fiche admin : envoyer en réparation, transformer, donner.
 * Chaque action crée l'enregistrement dans la gestion concernée et ouvre sa fiche.
 */
class CycleController extends Controller
{
    public function __construct(private CycleVetement $cycle) {}

    public function reparer(Request $request, Vetement $vetement): RedirectResponse
    {
        $atelier = Atelier::query()->findOrFail($request->validate(
            ['atelier_id' => ['required', 'integer', 'exists:ateliers,id']],
            ['atelier_id.required' => 'Choisissez un atelier.', 'atelier_id.exists' => 'Cet atelier n\'existe pas.'],
        )['atelier_id']);

        $demande = $this->cycle->envoyerEnReparation($vetement, $atelier);

        return redirect()->route('admin.ateliers.demandes.show', $demande)
            ->with('success', "« {$vetement->titre} » a été envoyé en réparation chez {$atelier->nom}. Diagnostic : {$demande->cout_affiche}, environ {$demande->delai_estime_jours} jour(s).");
    }

    public function transformer(Vetement $vetement): RedirectResponse
    {
        $projet = $this->cycle->transformerEnUpcycling($vetement);

        return redirect()->route('admin.upcycling.projets.edit', $projet)
            ->with('success', "Projet d'upcycling créé en brouillon à partir de « {$vetement->titre} » : complétez-le puis publiez-le.");
    }

    public function donner(Request $request, Vetement $vetement): RedirectResponse
    {
        $association = Association::query()->findOrFail($request->validate(
            ['association_id' => ['required', 'integer', 'exists:associations,id']],
            ['association_id.required' => 'Choisissez une association.', 'association_id.exists' => 'Cette association n\'existe pas.'],
        )['association_id']);

        $this->cycle->donnerAAssociation($vetement, $association);

        return redirect()->route('admin.depot.vetements.show', $vetement)
            ->with('success', "« {$vetement->titre} » a été donné à {$association->nom}.");
    }
}
