<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Services\Dons\Ia\RedactionIa;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Espace association : le responsable met à jour la présentation de son association (avec l'aide de l'IA s'il le souhaite)
 * et consulte son rapport d'impact mensuel.
 */
class EspacePresentationController extends Controller
{
    use AuthorizesRequests;

    public function edit(Association $association): View
    {
        $this->authorize('modifierPresentation', $association);

        return view('dons.espace.presentation', compact('association'));
    }

    public function update(Request $request, Association $association): RedirectResponse
    {
        $this->authorize('modifierPresentation', $association);

        $association->update($request->validate([
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'besoins' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins :min caractères.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
            'besoins.required' => 'Veuillez préciser les besoins de l\'association.',
            'besoins.min' => 'Les besoins doivent contenir au moins :min caractères.',
            'besoins.max' => 'Les besoins ne peuvent pas dépasser :max caractères.',
        ]));

        return redirect()->route('dons.espace.presentation.edit', $association)
            ->with('success', 'La présentation de l\'association a été mise à jour.');
    }

    public function rapport(Request $request, Association $association, RedactionIa $redaction): View
    {
        $this->authorize('view', $association);

        $mois = Carbon::createFromFormat('!Y-m', preg_match('/^\d{4}-\d{2}$/', (string) $request->query('mois')) ? $request->query('mois') : now()->format('Y-m'));
        if ($mois->isFuture()) {
            $mois = now()->startOfMonth();
        }

        return view('dons.espace.rapport', [
            'association' => $association,
            'mois' => $mois,
            'stats' => $redaction->statistiquesDuMois($association, $mois),
        ]);
    }
}
