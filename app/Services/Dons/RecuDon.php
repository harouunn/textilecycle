<?php

namespace App\Services\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Don;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * Reçu PDF d'un don reçu par une association. Le QR code renvoie vers la page publique de vérification.
 * Le QR code est un SVG (bacon/bacon-qr-code) : pas besoin de l'extension GD ni d'Imagick.
 */
class RecuDon
{
    public const DISQUE = 'local';

    public function chemin(Don $don): string
    {
        return 'dons/recus/'.$this->reference($don).'.pdf';
    }

    /**
     * Génère (ou régénère) le PDF et l'enregistre sur le disque privé.
     */
    public function generer(Don $don): string
    {
        Storage::disk(self::DISQUE)->put($this->chemin($don), $this->contenu($don));

        return $this->chemin($don);
    }

    /**
     * Contenu du PDF enregistré, généré à la volée s'il manque (ex. après une restauration de base).
     */
    public function pdf(Don $don): string
    {
        $disque = Storage::disk(self::DISQUE);

        if (! $disque->exists($this->chemin($don))) {
            $this->generer($don);
        }

        return $disque->get($this->chemin($don));
    }

    public function contenu(Don $don): string
    {
        $don->loadMissing(['user', 'association', 'campagne', 'historiques']);
        $url = route('dons.verifier', $this->reference($don));
        $reception = $don->historiques->last(fn ($etape) => $etape->nouveau_statut === DonStatut::Recu);

        return Pdf::loadView('dons.recu.pdf', [
            'don' => $don,
            'dateReception' => $reception?->created_at ?? $don->updated_at,
            'url' => $url,
            'qr' => $this->qrDataUri($url),
            'impact' => app(ImpactCalculator::class)->pourDon($don),
        ])->setPaper('a4')->output();
    }

    public function qrDataUri(string $contenu, int $taille = 160): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($taille, 1), new SvgImageBackEnd)))->writeString($contenu);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function reference(Don $don): string
    {
        return $don->reference ?? throw new LogicException("Le don n°{$don->id} n'a pas encore de reçu (il n'a pas été reçu).");
    }
}
