<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\VerificationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerifyEmailController extends Controller
{
    /**
     * Vérifie le code à 6 chiffres saisi par l'utilisateur.
     */
    public function __invoke(Request $request, VerificationEmail $verification): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->accueil());
        }

        $request->validate(['code' => ['required', 'string']], ['code.required' => 'Saisissez le code à 6 chiffres reçu par e-mail.']);

        $resultat = $verification->verifier($user, $request->input('code'));
        if ($resultat !== 'ok') {
            throw ValidationException::withMessages(['code' => match ($resultat) {
                'expire' => 'Ce code a expiré : demandez-en un nouveau.',
                'bloque' => 'Trop d\'essais : demandez un nouveau code.',
                default => 'Code incorrect. Vérifiez l\'e-mail reçu et réessayez.',
            }]);
        }

        return redirect()->intended($user->accueil())->with('status', 'Adresse e-mail confirmée. Bienvenue sur TexTileCycle !');
    }
}
