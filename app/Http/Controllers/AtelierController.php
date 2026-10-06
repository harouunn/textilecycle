<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtelierRequest;
use App\Models\Atelier;
use Illuminate\Http\Request;

class AtelierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ateliers = Atelier::latest()->paginate(15);
        return view('admin.ateliers.index', compact('ateliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ateliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AtelierRequest $request)
    {
        Atelier::create($request->validated());

        return redirect()->route('admin.ateliers.index')
            ->with('success', 'Atelier créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Atelier $atelier)
    {
        return view('admin.ateliers.show', compact('atelier'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Atelier $atelier)
    {
        return view('admin.ateliers.edit', compact('atelier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AtelierRequest $request, Atelier $atelier)
    {
        $atelier->update($request->validated());

        return redirect()->route('admin.ateliers.index')
            ->with('success', 'Atelier mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Atelier $atelier)
    {
        $atelier->delete();

        return redirect()->route('admin.ateliers.index')
            ->with('success', 'Atelier supprimé avec succès.');
    }
}
