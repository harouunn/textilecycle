<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $user = $request->user();

        // Compte protégé par la double authentification : le mot de passe ne suffit pas,
        // on le déconnecte et on demande le code de son application.
        if ($user->aDoubleAuthentification()) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->put([
                DefiDeuxFacteursController::SESSION_ID => $user->id,
                DefiDeuxFacteursController::SESSION_SOUVENIR => $request->boolean('remember'),
            ]);

            return redirect()->route('deux-facteurs.create');
        }

        $request->session()->regenerate();

        // Un admin arrive sur le back office, un client sur le site public.
        return redirect()->intended($user->accueil());
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
