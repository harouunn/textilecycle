<?php

namespace App\Http\Controllers\Ateliers;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Annuaire public des ateliers partenaires : seuls les ateliers actifs sont visibles.
 */
class AtelierController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'ville' => ['nullable', 'string', 'max:100'],
        ]);

        $ateliers = Atelier::query()
            ->actif()
            ->when($filters['ville'] ?? null, fn (Builder $query, string $ville) => $query->where('ville', $ville))
            ->orderBy('nom')
            ->paginate(9)
            ->withQueryString();

        return view('ateliers.index', [
            'ateliers' => $ateliers,
            'filters' => $filters,
            'villes' => Atelier::query()->actif()->distinct()->orderBy('ville')->pluck('ville'),
        ]);
    }

    public function show(Atelier $atelier): View
    {
        abort_unless($atelier->actif, 404);

        return view('ateliers.show', compact('atelier'));
    }
}
