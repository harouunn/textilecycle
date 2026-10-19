<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\DoubleAuthentification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Page « Sécurité du compte » : activer, confirmer et désactiver la double authentification par QR code.
 *
 * Étapes : activer (un secret est créé) → scanner le QR code → confirmer avec un premier code à 6 chiffres
 * (la 2FA devient active) → noter les codes de secours, affichés une seule fois.
 */
class SecuriteController extends Controller
{
    public function __construct(private DoubleAuthentification $deuxFacteurs) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $enAttente = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        return view('auth.securite', [
            'user' => $user,
            'enAttente' => $enAttente,
            'qrCode' => $enAttente ? $this->deuxFacteurs->qrCodeSvg($user, $user->two_factor_secret) : null,
            'codesSecours' => session('codes_secours'),
        ]);
    }

    public function activer(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => $this->deuxFacteurs->genererSecret(),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()->route('securite.show');
    }

    public function confirmer(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']], ['code.required' => 'Saisissez le code à 6 chiffres affiché par votre application.']);
        $user = $request->user();

        if ($user->two_factor_secret === null || ! $this->deuxFacteurs->codeValide($user->two_factor_secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'Code incorrect ou expiré. Vérifiez l\'heure de votre téléphone et réessayez.']);
        }

        $codes = $this->deuxFacteurs->genererCodesSecours();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();

        return redirect()->route('securite.show')
            ->with('success', 'La double authentification est activée. Notez vos codes de secours.')
            ->with('codes_secours', $codes);
    }

    public function regenererCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->aDoubleAuthentification(), 404);

        $codes = $this->deuxFacteurs->genererCodesSecours();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return redirect()->route('securite.show')
            ->with('success', 'Nouveaux codes de secours générés : les anciens ne fonctionnent plus.')
            ->with('codes_secours', $codes);
    }

    public function desactiver(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'Saisissez votre mot de passe pour confirmer.',
            'password.current_password' => 'Le mot de passe est incorrect.',
        ]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()->route('securite.show')->with('success', 'La double authentification est désactivée.');
    }
}
