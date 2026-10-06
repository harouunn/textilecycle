<?php

namespace App\Http\Controllers\Admin\Upcycling;

use App\Enums\Upcycling\Difficulte;
use App\Enums\Upcycling\StatutProjet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\ProjetUpcyclingRequest;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\EnregistrerAvecPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjetUpcyclingController extends Controller
{
    public function __construct(private readonly EnregistrerAvecPhotos $enregistrement) {}

    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'difficulte' => ['nullable', Rule::enum(Difficulte::class)],
            'statut' => ['nullable', Rule::enum(StatutProjet::class)],
        ]);

        $projets = ProjetUpcycling::query()
            ->with('user')
            ->withCount('etapes')
            ->when($filtres['q'] ?? null, function ($query, string $recherche) {
                $query->where(function ($query) use ($recherche) {
                    $query->where('titre', 'like', "%{$recherche}%")
                        ->orWhere('vetement_origine', 'like', "%{$recherche}%")
                        ->orWhere('resultat', 'like', "%{$recherche}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$recherche}%"));
                });
            })
            ->when($filtres['difficulte'] ?? null, fn ($query, string $difficulte) => $query->where('difficulte', $difficulte))
            ->when($filtres['statut'] ?? null, fn ($query, string $statut) => $query->where('statut', $statut))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.upcycling.projets.index', [
            'projets' => $projets,
            'difficultes' => Difficulte::options(),
            'statuts' => StatutProjet::options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.upcycling.projets.create', [
            'projet' => new ProjetUpcycling,
            'difficultes' => Difficulte::options(),
            'statuts' => StatutProjet::options(),
        ]);
    }

    public function store(ProjetUpcyclingRequest $request): RedirectResponse
    {
        $projet = new ProjetUpcycling;
        $this->enregistrement->enregistrer($projet, [...$request->donnees(), 'user_id' => $request->user()->id], $request, ['photo_avant', 'photo_apres'], 'upcycling/projets');

        return redirect()
            ->route('admin.upcycling.projets.show', $projet)
            ->with('success', 'Le projet « '.$projet->titre.' » a été créé. Ajoutez maintenant ses étapes.');
    }

    public function show(ProjetUpcycling $projet): View
    {
        $projet->load(['user', 'etapes']);

        return view('admin.upcycling.projets.show', [
            'projet' => $projet,
            'prochainNumero' => $projet->prochainNumeroEtape(),
        ]);
    }

    public function edit(ProjetUpcycling $projet): View
    {
        return view('admin.upcycling.projets.edit', [
            'projet' => $projet,
            'difficultes' => Difficulte::options(),
            'statuts' => StatutProjet::options(),
        ]);
    }

    public function update(ProjetUpcyclingRequest $request, ProjetUpcycling $projet): RedirectResponse
    {
        $this->enregistrement->enregistrer($projet, $request->donnees($projet), $request, ['photo_avant', 'photo_apres'], 'upcycling/projets');

        return redirect()
            ->route('admin.upcycling.projets.show', $projet)
            ->with('success', 'Le projet a été mis à jour.');
    }

    public function destroy(ProjetUpcycling $projet): RedirectResponse
    {
        $projet->delete();

        return redirect()
            ->route('admin.upcycling.projets.index')
            ->with('success', 'Le projet « '.$projet->titre.' » a été supprimé.');
    }
}
