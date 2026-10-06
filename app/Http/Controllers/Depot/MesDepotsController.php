<?php

namespace App\Http\Controllers\Depot;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Depot\Concerns\GerePhotoVetement;
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
    use GerePhotoVetement;

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

    public function store(StoreVetementRequest $request): RedirectResponse
    {
        $vetement = Vetement::query()->create([
            ...$this->avecPhoto($request, $request->validated()),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('depot.mes-depots.index')
            ->with('success', "Merci ! Votre vêtement « {$vetement->titre} » a bien été déposé.");
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
        $vetement->update($this->avecPhoto($request, $request->validated(), $vetement));

        return redirect()
            ->route('depot.mes-depots.index')
            ->with('success', "Votre vêtement « {$vetement->titre} » a été modifié.");
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
