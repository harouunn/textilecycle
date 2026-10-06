<?php

namespace App\Http\Controllers\Depot;

use App\Enums\Depot\Moderation;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Depot\Concerns\AnalysePhotoVetement;
use App\Http\Controllers\Depot\Concerns\GerePhotoVetement;
use App\Http\Requests\Depot\AnalyserVetementRequest;
use App\Http\Requests\Depot\StoreVetementRequest;
use App\Http\Requests\Depot\UpdateVetementRequest;
use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * « Déposer un vêtement » et « Mes dépôts » : l'utilisateur connecté ne gère que ses propres vêtements.
 */
class MesDepotsController extends Controller
{
    use AnalysePhotoVetement, GerePhotoVetement;

    public function index(Request $request): View
    {
        $vetements = Vetement::query()
            ->with('categorie')
            ->whereBelongsTo($request->user())
            ->latest('date_depot')
            ->latest('id')
            ->paginate(10);

        return view('depot.mes-depots.index', compact('vetements'));
    }

    public function create(): View
    {
        return view('depot.mes-depots.create', [
            'vetement' => new Vetement(['date_depot' => today()]),
            'categories' => $this->categories(),
        ]);
    }

    /**
     * « Analyser la photo » : classement automatique puis retour au formulaire pré-rempli.
     */
    public function analyser(AnalyserVetementRequest $request): RedirectResponse
    {
        return $this->analyserPhoto($request, 'depot.mes-depots.create');
    }

    public function store(StoreVetementRequest $request): RedirectResponse
    {
        $vetement = Vetement::query()->create([
            ...$this->avecPhoto($request, $request->validated()),
            'user_id' => $request->user()->id,
            'moderation' => Moderation::EnAttente,
        ]);

        return redirect()
            ->route('depot.mes-depots.index')
            ->with('success', "Merci ! Votre vêtement « {$vetement->titre} » a bien été déposé. Il apparaîtra dans le catalogue après validation.");
    }

    public function edit(Vetement $vetement): View
    {
        Gate::authorize('update', $vetement);

        return view('depot.mes-depots.edit', [
            'vetement' => $vetement,
            'categories' => $this->categories(),
        ]);
    }

    public function update(UpdateVetementRequest $request, Vetement $vetement): RedirectResponse
    {
        // Toute modification (photo comprise) est revalidée par l'admin.
        $vetement->update([
            ...$this->avecPhoto($request, $request->validated(), $vetement),
            'moderation' => Moderation::EnAttente,
            'motif_refus' => null,
        ]);

        return redirect()
            ->route('depot.mes-depots.index')
            ->with('success', "Votre vêtement « {$vetement->titre} » a été modifié. Il sera de nouveau validé avant publication.");
    }

    public function destroy(Vetement $vetement): RedirectResponse
    {
        Gate::authorize('delete', $vetement);

        $vetement->deletePhoto();
        $vetement->delete();

        return redirect()
            ->route('depot.mes-depots.index')
            ->with('success', "Votre vêtement « {$vetement->titre} » a été supprimé.");
    }

    /**
     * @return Collection<int, string>
     */
    private function categories(): Collection
    {
        return Categorie::query()->orderBy('nom')->pluck('nom', 'id');
    }
}
