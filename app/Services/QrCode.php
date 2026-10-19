<?php

namespace App\Services;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;

/**
 * Génère des QR codes en PNG (GD), utilisables dans une page ou dans un PDF (image en data URI).
 */
class QrCode
{
    public function pngDataUri(string $contenu, int $taille = 220): string
    {
        $png = (new Writer(new GDLibRenderer($taille, 2)))->writeString($contenu);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
