<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\VerificationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Page « Vérifiez votre e-mail » : saisie du code à 6 chiffres reçu par e-mail.
     */
    public function __invoke(Request $request, VerificationEmail $verification): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->accueil());
        }

        return view('auth.verify-email', [
            'codeDev' => $verification->modeDeveloppement() ? session(VerificationEmail::SESSION_CODE_DEV) : null,
        ]);
    }
}
