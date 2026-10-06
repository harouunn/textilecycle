<?php

namespace App\Http\Controllers\Admin\Ateliers;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Ateliers\TypeVetement;
use App\Http\Controllers\Ateliers\Concerns\GerePhotoDemande;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ateliers\StoreDemandeReparationRequest;
use App\Http\Requests\Ateliers\TraiterDemandeReparationRequest;
use App\Http\Requests\Ateliers\UpdateDemandeReparationRequest;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DemandeReparationController extends Controller
{
    use GerePhotoDemande;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'atelier' => ['nullable', 'integer'],
            'statut' => ['nullable', Rule::enum(StatutDemande::class)],
        ]);

        $demandes = DemandeReparation::query()
            ->with(['atelier', 'user'])
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.ateliers.demandes.index', [
            'demandes' => $demandes,
            'filters' => $filters,
            'ateliers' => Atelier::query()->orderBy('nom')->pluck('nom', 'id'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        return view('admin.ateliers.demandes.create', $this->formData(new DemandeReparation([
            'user_id' => $request->user()->id,
            'type_vetement' => TypeVetement::Haut,
        ])));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDemandeReparationRequest $request): RedirectResponse
    {
        $demande = new DemandeReparation($this->avecPhoto($request, $request->validated()));
        $demande->diagnostiquer()->save();

        return redirect()
            ->route('admin.ateliers.demandes.show', $demande)
            ->with('success', "La demande « {$demande->titre} » a été créée et diagnostiquée.");
    }

    /**
     * Display the specified resource.
     */
    public function show(DemandeReparation $demande): View
    {
        $demande->load(['atelier', 'user']);

        return view('admin.ateliers.demandes.show', compact('demande'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DemandeReparation $demande): View
    {
        return view('admin.ateliers.demandes.edit', $this->formData($demande));
    }

    /**
     * Update the specified resource in storage. Le diagnostic est recalculé.
     */
    public function update(UpdateDemandeReparationRequest $request, DemandeReparation $demande): RedirectResponse
    {
        $demande->fill($this->avecPhoto($request, $request->validated(), $demande));
        $demande->diagnostiquer()->save();

        return redirect()
            ->route('admin.ateliers.demandes.show', $demande)
            ->with('success', "La demande « {$demande->titre} » a été modifiée et son diagnostic recalculé.");
    }

    /**
     * Réponse de l'atelier : statut, coût final, date prévue et message au client.
     */
    public function traiter(TraiterDemandeReparationRequest $request, DemandeReparation $demande): RedirectResponse
    {
        $demande->update($request->validated());

        return redirect()
            ->route('admin.ateliers.demandes.show', $demande)
            ->with('success', "La demande est maintenant « {$demande->statut->label()} ».");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DemandeReparation $demande): RedirectResponse
    {
        $demande->deletePhoto();
        $demande->delete();

        return redirect()
            ->route('admin.ateliers.demandes.index')
            ->with('success', "La demande « {$demande->titre} » a été supprimée.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(DemandeReparation $demande): array
    {
        return [
            'demande' => $demande,
            'ateliers' => Atelier::query()->orderBy('nom')->pluck('nom', 'id'),
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
