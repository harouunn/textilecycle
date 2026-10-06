<?php

namespace App\Http\Controllers\Dons\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\DonRequest;
use App\Http\Requests\Dons\UpdateDonStatutRequest;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'association_id' => $request->query('association_id'),
            'statut' => $request->query('statut'),
            'type_article' => $request->query('type_article'),
        ];

        $dons = Don::query()
            ->with(['association', 'user'])
            ->when($filters['q'] !== '', fn ($query) => $query->where(function ($query) use ($filters) {
                $search = "%{$filters['q']}%";
                $query->where('message', 'like', $search)
                    ->orWhere('adresse_collecte', 'like', $search)
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('association', fn ($q) => $q->where('nom', 'like', $search));
            }))
            ->when($filters['association_id'], fn ($query, $id) => $query->where('association_id', $id))
            ->when(array_key_exists((string) $filters['statut'], Don::STATUTS), fn ($query) => $query->where('statut', $filters['statut']))
            ->when(array_key_exists((string) $filters['type_article'], Don::TYPES), fn ($query) => $query->where('type_article', $filters['type_article']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $associations = Association::orderBy('nom')->pluck('nom', 'id');

        return view('admin.dons.dons.index', compact('dons', 'associations', 'filters'));
    }

    public function create(Request $request): View
    {
        $don = new Don([
            'association_id' => $request->query('association_id'),
            'statut' => 'propose',
            'mode_remise' => 'depot_sur_place',
        ]);

        return view('admin.dons.dons.create', ['don' => $don, ...$this->formOptions()]);
    }

    public function store(DonRequest $request): RedirectResponse
    {
        $don = Don::create($request->validated());

        return redirect()->route('admin.dons.dons.show', $don)
            ->with('success', "Le don n°{$don->id} a été enregistré.");
    }

    public function show(Don $don): View
    {
        $don->load(['association', 'user']);

        return view('admin.dons.dons.show', compact('don'));
    }

    public function edit(Don $don): View
    {
        return view('admin.dons.dons.edit', ['don' => $don, ...$this->formOptions()]);
    }

    public function update(DonRequest $request, Don $don): RedirectResponse
    {
        $don->update($request->validated());

        return redirect()->route('admin.dons.dons.show', $don)
            ->with('success', "Le don n°{$don->id} a été mis à jour.");
    }

    public function updateStatut(UpdateDonStatutRequest $request, Don $don): RedirectResponse
    {
        $don->update($request->validated());

        return back()->with('success', "Le statut du don n°{$don->id} est maintenant « {$don->statutLabel()} ».");
    }

    public function destroy(Don $don): RedirectResponse
    {
        $don->delete();

        return redirect()->route('admin.dons.dons.index')
            ->with('success', "Le don n°{$don->id} a été supprimé.");
    }

    private function formOptions(): array
    {
        return [
            'associations' => Association::orderBy('nom')->pluck('nom', 'id'),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ];
    }
}
