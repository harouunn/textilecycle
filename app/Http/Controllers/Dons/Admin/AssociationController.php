<?php

namespace App\Http\Controllers\Dons\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\AssociationRequest;
use App\Models\Association;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssociationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Association::class);

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
        $this->authorize('create', Association::class);

        return view('admin.dons.associations.create', [
            'association' => new Association(['active' => true, 'capacite_mensuelle_kg' => 0]),
            'responsables' => $this->responsables(),
        ]);
    }

    public function store(AssociationRequest $request): RedirectResponse
    {
        $this->authorize('create', Association::class);

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
        $this->authorize('view', $association);

        $association->load('responsable')->loadCount('dons')->loadSum('dons', 'poids_kg');
        $dons = $association->dons()->with('user')->latest()->paginate(10);

        return view('admin.dons.associations.show', compact('association', 'dons'));
    }

    public function edit(Association $association): View
    {
        $this->authorize('update', $association);

        return view('admin.dons.associations.edit', ['association' => $association, 'responsables' => $this->responsables()]);
    }

    public function update(AssociationRequest $request, Association $association): RedirectResponse
    {
        $this->authorize('update', $association);

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

    /**
     * Archive (soft delete): the association leaves the site and the back office lists,
     * its donations and logo are kept so their history stays readable.
     */
    public function destroy(Association $association): RedirectResponse
    {
        $this->authorize('delete', $association);

        $association->delete();

        return redirect()->route('admin.dons.associations.index')
            ->with('success', "L'association « {$association->nom} » a été archivée. Ses dons restent consultables.");
    }

    /**
     * @return Collection<int, User>
     */
    private function responsables(): Collection
    {
        return User::orderBy('name')->get(['id', 'name', 'email']);
    }

    private function deleteLogo(Association $association): void
    {
        if ($association->logo) {
            Storage::disk('public')->delete($association->logo);
        }
    }
}
