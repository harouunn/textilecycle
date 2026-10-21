<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Models\Don;
use Illuminate\View\View;

/**
 * Page publique ouverte par le QR code d'un reçu : confirme que le reçu est authentique.
 * Elle n'affiche que le strict nécessaire (pas d'e-mail, d'adresse ni de nom de famille du donateur).
 */
class VerificationRecuController extends Controller
{
    public function __invoke(string $reference): View
    {
        $don = Don::query()
            ->where('reference', $reference)
            ->with(['association', 'user'])
            ->first();

        return view('dons.recu.verifier', ['don' => $don, 'reference' => $reference]);
    }
}
