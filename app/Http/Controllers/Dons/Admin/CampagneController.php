<?php

namespace App\Http\Controllers\Dons\Admin;

use App\Enums\Dons\StatutCampagne;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\CampagneRequest;
use App\Models\Association;
use App\Models\CampagneCollecte;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CampagneController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CampagneCollecte::class);

        $filters = [
            'q' => trim((string) $request->query('q')),
            'association_id' => $request->query('association_id'),
            'statut' => $request->query('statut'),
        ];
        $statut = StatutCampagne::tryFrom((string) $filters['statut']);

        $campagnes = CampagneCollecte::query()
            ->with('association')
            ->avecProgression()
            ->when($filters['q'] !== '', fn ($query) => $query->where('titre', 'like', "%{$filters['q']}%"))
            ->when($filters['association_id'], fn ($query, $id) => $query->where('association_id', $id))
            ->when($statut, fn ($query) => $query->where('statut', $statut))
            ->orderByDesc('date_debut')
            ->paginate(10)
            ->withQueryString();

        $associations = Association::orderBy('nom')->pluck('nom', 'id');

        return view('admin.dons.campagnes.index', compact('campagnes', 'associations', 'filters'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CampagneCollecte::class);

        $campagne = new CampagneCollecte([
            'association_id' => $request->query('association_id'),
            'statut' => StatutCampagne::Brouillon,
            'date_debut' => today(),
            'date_fin' => today()->addMonth(),
        ]);

        return view('admin.dons.campagnes.create', ['campagne' => $campagne, 'associations' => $this->associations()]);
    }

    public function store(CampagneRequest $request): RedirectResponse
    {
        $this->authorize('create', CampagneCollecte::class);

        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('campagnes', 'public');
        }

        $campagne = CampagneCollecte::create($data);

        return redirect()->route('admin.dons.campagnes.show', $campagne)
            ->with('success', "La campagne « {$campagne->titre} » a été créée.");
    }

    public function show(CampagneCollecte $campagne): View
    {
        $this->authorize('view', $campagne);

        $campagne->load('association')->loadSum(['dons as kg_collectes' => fn ($q) => $q->whereIn('statut', CampagneCollecte::STATUTS_COMPTES)], 'poids_kg');
        $dons = $campagne->dons()->with('user')->latest()->paginate(10);

        return view('admin.dons.campagnes.show', compact('campagne', 'dons'));
    }

    public function edit(CampagneCollecte $campagne): View
    {
        $this->authorize('update', $campagne);

        return view('admin.dons.campagnes.edit', ['campagne' => $campagne, 'associations' => $this->associations()]);
    }

    public function update(CampagneRequest $request, CampagneCollecte $campagne): RedirectResponse
    {
        $this->authorize('update', $campagne);

        $data = $request->validated();
        unset($data['image']);
        $data['type_articles_cibles'] = $data['type_articles_cibles'] ?? null;

        if ($request->hasFile('image')) {
            $this->supprimerImage($campagne);
            $data['image'] = $request->file('image')->store('campagnes', 'public');
        } elseif ($request->boolean('supprimer_image')) {
            $this->supprimerImage($campagne);
            $data['image'] = null;
        }

        $campagne->update($data);

        return redirect()->route('admin.dons.campagnes.show', $campagne)
            ->with('success', "La campagne « {$campagne->titre} » a été mise à jour.");
    }

    public function destroy(CampagneCollecte $campagne): RedirectResponse
    {
        $this->authorize('delete', $campagne);

        $this->supprimerImage($campagne);
        $campagne->delete();

        return redirect()->route('admin.dons.campagnes.index')
            ->with('success', "La campagne « {$campagne->titre} » a été supprimée. Ses dons sont conservés.");
    }

    private function associations()
    {
        return Association::orderBy('nom')->pluck('nom', 'id');
    }

    private function supprimerImage(CampagneCollecte $campagne): void
    {
        if ($campagne->image) {
            Storage::disk('public')->delete($campagne->image);
        }
    }
}
