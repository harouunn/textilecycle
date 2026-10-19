<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use App\Models\Vetement;
use App\Services\Depot\ImpactEcologique;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Page d'accueil : le cycle TexTileCycle avec les vrais chiffres des quatre gestions,
 * les derniers vêtements du catalogue et l'impact écologique cumulé.
 * Les compteurs sont mis en cache 10 minutes : l'accueil reste rapide même avec beaucoup de données.
 */
class AccueilController extends Controller
{
    public function __invoke(ImpactEcologique $impact): View
    {
        $chiffres = Cache::remember('accueil.chiffres', now()->addMinutes(10), fn () => [
            'vetements' => Vetement::query()->auCatalogue()->count(),
            'ateliers' => Atelier::query()->actif()->count(),
            'projets' => ProjetUpcycling::query()->publie()->count(),
            'associations' => Association::query()->where('active', true)->count(),
            'impact' => $impact->global(),
        ]);

        return view('front.home', [
            'chiffres' => $chiffres,
            'derniersVetements' => Vetement::query()->with('categorie')->auCatalogue()->latest('id')->limit(8)->get(),
            'associations' => Association::query()->where('active', true)->orderBy('nom')->limit(6)->get(['id', 'nom', 'ville']),
        ]);
    }
}
