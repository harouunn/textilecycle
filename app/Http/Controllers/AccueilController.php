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

        $derniers = Vetement::query()->with('categorie')->auCatalogue()->latest('id')->limit(8)->get();

        return view('front.home', [
            'chiffres' => $chiffres,
            'mosaique' => $this->mosaique(),
            'derniersVetements' => $derniers,
            'associations' => Association::query()->where('active', true)->orderBy('nom')->limit(6)->get(['id', 'nom', 'ville']),
        ]);
    }

    /**
     * Photos de la mosaïque animée de l'accueil, réparties en 3 colonnes de 6.
     * Les vrais vêtements du catalogue (avec photo) passent en premier, complétés par les photos du template.
     *
     * @return list<list<array{src: string, alt: string, lien: string}>>
     */
    private function mosaique(): array
    {
        $photos = Vetement::query()->auCatalogue()->whereNotNull('photo')->latest('id')->limit(18)->get()
            ->map(fn (Vetement $v) => ['src' => $v->miniature_url, 'alt' => $v->titre, 'lien' => route('depot.catalogue.show', $v)])
            ->all();

        $template = ['product-item-1', 'banner-image-1', 'product-item-2', 'insta-item1', 'product-item-3', 'banner-image-3', 'product-item-4', 'insta-item2',
            'product-item-5', 'banner-image-5', 'product-item-6', 'insta-item3', 'product-item-7', 'cat-item1', 'product-item-8', 'insta-item4', 'product-item-9', 'cat-item2'];
        foreach ($template as $image) {
            if (count($photos) >= 18) {
                break;
            }
            $photos[] = ['src' => asset("front/images/{$image}.jpg"), 'alt' => '', 'lien' => route('depot.catalogue.index')];
        }

        return array_chunk($photos, 6);
    }
}
