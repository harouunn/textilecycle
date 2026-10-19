<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\DoubleAuthentification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Seconde étape de la connexion : après un mot de passe correct, un compte protégé par la 2FA
 * doit saisir le code de son application (ou un code de secours) avant d'être réellement connecté.
 */
class DefiDeuxFacteursController extends Controller
{
    public const SESSION_ID = 'connexion.id';

    public const SESSION_SOUVENIR = 'connexion.souvenir';

    private const ESSAIS_MAX = 5;

    public function __construct(private DoubleAuthentification $deuxFacteurs) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->utilisateurEnAttente($request)) {
            return redirect()->route('login');
        }

        return view('auth.deux-facteurs');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->utilisateurEnAttente($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $cle = 'deux-facteurs:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, self::ESSAIS_MAX)) {
            throw ValidationException::withMessages([
                'code' => 'Trop d\'essais. Réessayez dans '.RateLimiter::availableIn($cle).' secondes.',
            ]);
        }

        $valide = $request->filled('code_secours')
            ? $this->deuxFacteurs->utiliserCodeSecours($user, $request->input('code_secours'))
            : $this->deuxFacteurs->codeValide($user->two_factor_secret, $request->input('code'));

        if (! $valide) {
            RateLimiter::hit($cle);
            throw ValidationException::withMessages([
                $request->filled('code_secours') ? 'code_secours' : 'code' => 'Code incorrect. Réessayez.',
            ]);
        }

        RateLimiter::clear($cle);
        Auth::login($user, (bool) $request->session()->pull(self::SESSION_SOUVENIR));
        $request->session()->forget(self::SESSION_ID);
        $request->session()->regenerate();

        return redirect()->intended($user->accueil());
    }

    private function utilisateurEnAttente(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION_ID);

        return $id ? User::query()->find($id) : null;
    }
}
