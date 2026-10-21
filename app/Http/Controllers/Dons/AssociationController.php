<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Services\Dons\ImpactCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Front office: browse the active partner associations (list, map, "près de moi").
 */
class AssociationController extends Controller
{
    public function index(Request $request, ImpactCalculator $impact): View
    {
        $ville = trim((string) $request->query('ville'));

        // « Près de moi » : position envoyée par le navigateur (géolocalisation), rayon en km.
        // Une position invalide est ignorée (pas de redirection : c'est une simple page de liste).
        $validation = Validator::make($request->only(['lat', 'lng', 'rayon']), [
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'rayon' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);
        $positionInvalide = $validation->fails();
        $position = $positionInvalide ? [] : $validation->validated();
        $presDeMoi = isset($position['lat'], $position['lng']);
        $rayon = (int) ($position['rayon'] ?? config('textilecycle.dons.rayon_km'));

        $associations = Association::active()
            ->when($ville !== '', fn ($query) => $query->where('ville', $ville))
            ->orderBy('nom')
            ->when($presDeMoi, fn ($query) => $query->procheDe((float) $position['lat'], (float) $position['lng'], $rayon))
            ->paginate(9)
            ->withQueryString();

        $villes = Association::active()->distinct()->orderBy('ville')->pluck('ville');

        // Tous les points de la carte (pas seulement la page affichée).
        $marqueurs = Association::active()
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->get(['id', 'nom', 'ville', 'latitude', 'longitude'])
            ->map(fn (Association $a) => [
                'nom' => $a->nom,
                'ville' => $a->ville,
                'lat' => (float) $a->latitude,
                'lng' => (float) $a->longitude,
                'url' => route('dons.associations.show', $a),
            ])
            ->values();

        return view('dons.associations.index', [
            'associations' => $associations,
            'villes' => $villes,
            'ville' => $ville,
            'marqueurs' => $marqueurs,
            'presDeMoi' => $presDeMoi,
            'positionInvalide' => $positionInvalide,
            'position' => $presDeMoi ? ['lat' => (float) $position['lat'], 'lng' => (float) $position['lng']] : null,
            'rayon' => $rayon,
            'impact' => $impact->global(),
        ]);
    }

    public function show(Association $association): View
    {
        abort_unless($association->active, 404);

        $association->loadCount(['dons as dons_recus_count' => fn ($query) => $query->where('statut', 'recu')]);
        $campagnes = $association->campagnes()->enCours()->avecProgression()->orderBy('date_fin')->get();

        return view('dons.associations.show', compact('association', 'campagnes'));
    }
}
