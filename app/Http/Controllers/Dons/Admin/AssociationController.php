<?php

namespace App\Http\Controllers\Dons\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\AssociationRequest;
use App\Models\Association;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssociationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $active = $request->query('active');

        $associations = Association::query()
            ->withCount('dons')
            ->withSum('dons', 'poids_kg')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nom', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(in_array($active, ['0', '1'], true), fn ($query) => $query->where('active', $active === '1'))
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('admin.dons.associations.index', compact('associations', 'search', 'active'));
    }

    public function create(): View
    {
        return view('admin.dons.associations.create', ['association' => new Association(['active' => true])]);
    }

    public function store(AssociationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('associations', 'public');
        }

        $association = Association::create($data);

        return redirect()->route('admin.dons.associations.show', $association)
            ->with('success', "L'association « {$association->nom} » a été créée.");
    }

    public function show(Association $association): View
    {
        $association->loadCount('dons')->loadSum('dons', 'poids_kg');
        $dons = $association->dons()->with('user')->latest()->paginate(10);

        return view('admin.dons.associations.show', compact('association', 'dons'));
    }

    public function edit(Association $association): View
    {
        return view('admin.dons.associations.edit', compact('association'));
    }

    public function update(AssociationRequest $request, Association $association): RedirectResponse
    {
        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $this->deleteLogo($association);
            $data['logo'] = $request->file('logo')->store('associations', 'public');
        } elseif ($request->boolean('supprimer_logo')) {
            $this->deleteLogo($association);
            $data['logo'] = null;
        }

        $association->update($data);

        return redirect()->route('admin.dons.associations.show', $association)
            ->with('success', "L'association « {$association->nom} » a été mise à jour.");
    }

    public function destroy(Association $association): RedirectResponse
    {
        $this->deleteLogo($association);
        $association->delete();

        return redirect()->route('admin.dons.associations.index')
            ->with('success', "L'association « {$association->nom} » et ses dons ont été supprimés.");
    }

    private function deleteLogo(Association $association): void
    {
        if ($association->logo) {
            Storage::disk('public')->delete($association->logo);
        }
    }
}
