<?php

namespace App\Http\Controllers\Admin\Depot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Depot\StoreCategorieRequest;
use App\Http\Requests\Depot\UpdateCategorieRequest;
use App\Models\Categorie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        $categories = Categorie::query()
            ->withCount('vetements')
            ->when($search, fn ($query) => $query->where('nom', 'like', "%{$search}%"))
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('admin.depot.categories.index', compact('categories', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.depot.categories.create', ['categorie' => new Categorie]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategorieRequest $request): RedirectResponse
    {
        $categorie = Categorie::query()->create($request->validated());

        return redirect()
            ->route('admin.depot.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été créée.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categorie $categorie): View
    {
        return view('admin.depot.categories.edit', compact('categorie'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategorieRequest $request, Categorie $categorie): RedirectResponse
    {
        $categorie->update($request->validated());

        return redirect()
            ->route('admin.depot.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été modifiée.");
    }

    /**
     * Remove the specified resource from storage.
     * Les vêtements de la catégorie sont supprimés en cascade : on supprime d'abord leurs photos.
     */
    public function destroy(Categorie $categorie): RedirectResponse
    {
        $categorie->vetements()->whereNotNull('photo')->get()->each->deletePhoto();
        $categorie->delete();

        return redirect()
            ->route('admin.depot.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été supprimée.");
    }
}
