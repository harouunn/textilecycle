<?php

namespace App\Http\Controllers\Admin\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutVetement;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Depot\Concerns\AnalysePhotoVetement;
use App\Http\Controllers\Depot\Concerns\GerePhotoVetement;
use App\Http\Requests\Depot\AnalyserVetementRequest;
use App\Http\Requests\Depot\RefuserVetementRequest;
use App\Http\Requests\Depot\StoreVetementRequest;
use App\Http\Requests\Depot\UpdateVetementRequest;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VetementController extends Controller
{
    use AnalysePhotoVetement, GerePhotoVetement;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'integer'],
            'etat' => ['nullable', Rule::enum(Etat::class)],
            'statut' => ['nullable', Rule::enum(StatutVetement::class)],
            'moderation' => ['nullable', Rule::enum(Moderation::class)],
        ]);

        $vetements = Vetement::query()
            ->with('categorie')
            ->filter($filters)
            ->latest('date_depot')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.depot.vetements.index', [
            'vetements' => $vetements,
            'filters' => $filters,
            'categories' => Categorie::query()->orderBy('nom')->pluck('nom', 'id'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        return view('admin.depot.vetements.create', $this->formData(new Vetement([
            'user_id' => $request->user()->id,
            'date_depot' => today(),
        ])));
    }

    /**
     * « Analyser la photo » : classement automatique puis retour au formulaire pré-rempli.
     */
    public function analyser(AnalyserVetementRequest $request): RedirectResponse
    {
        return $this->analyserPhoto($request, 'admin.depot.vetements.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVetementRequest $request): RedirectResponse
    {
        $vetement = Vetement::query()->create($this->avecPhoto($request, $request->validated()));

        return redirect()
            ->route('admin.depot.vetements.index')
            ->with('success', "Le vêtement « {$vetement->titre} » a été ajouté.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Vetement $vetement): View
    {
        $vetement->load(['categorie', 'user']);

        return view('admin.depot.vetements.show', compact('vetement'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vetement $vetement): View
    {
        return view('admin.depot.vetements.edit', $this->formData($vetement));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVetementRequest $request, Vetement $vetement): RedirectResponse
    {
        $vetement->update($this->avecPhoto($request, $request->validated(), $vetement));

        return redirect()
            ->route('admin.depot.vetements.index')
            ->with('success', "Le vêtement « {$vetement->titre} » a été modifié.");
    }

    /**
     * Publie le vêtement dans le catalogue.
     */
    public function approuver(Request $request, Vetement $vetement): RedirectResponse
    {
        $vetement->update(['moderation' => Moderation::Approuve, 'motif_refus' => null]);

        // Bouton rapide de la page « Dépôts à valider » : on y revient pour traiter le suivant.
        $redirection = $request->input('retour') === 'liste'
            ? redirect()->route('admin.depot.vetements.index', ['moderation' => Moderation::EnAttente->value])
            : redirect()->route('admin.depot.vetements.show', $vetement);

        return $redirection->with('success', "Le vêtement « {$vetement->titre} » est approuvé : il apparaît dans le catalogue.");
    }

    /**
     * Refuse le dépôt : il reste hors du catalogue et le déposant voit le motif.
     */
    public function refuser(RefuserVetementRequest $request, Vetement $vetement): RedirectResponse
    {
        $vetement->update(['moderation' => Moderation::Refuse, 'motif_refus' => $request->validated('motif_refus')]);

        return redirect()
            ->route('admin.depot.vetements.show', $vetement)
            ->with('success', "Le vêtement « {$vetement->titre} » a été refusé.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vetement $vetement): RedirectResponse
    {
        $vetement->deletePhoto();
        $vetement->delete();

        return redirect()
            ->route('admin.depot.vetements.index')
            ->with('success', "Le vêtement « {$vetement->titre} » a été supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Vetement $vetement): array
    {
        return [
            'vetement' => $vetement,
            'categories' => Categorie::query()->orderBy('nom')->pluck('nom', 'id'),
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
