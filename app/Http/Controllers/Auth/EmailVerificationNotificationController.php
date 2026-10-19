<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\VerificationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Envoie un nouveau code de vérification (l'ancien ne fonctionne plus).
     */
    public function store(Request $request, VerificationEmail $verification): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->accueil());
        }

        return $verification->envoyerCode($request->user())
            ? back()->with('status', 'Un nouveau code vient de vous être envoyé.')
            : back()->withErrors(['code' => 'L\'e-mail n\'a pas pu être envoyé. Réessayez dans quelques instants.']);
    }
}
