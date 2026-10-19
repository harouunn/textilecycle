<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\CaptchaValide;
use App\Services\Auth\Captcha;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            // Mêmes règles que les indicateurs affichés sous le champ : 8 caractères, majuscule, minuscule et chiffre.
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/\d/'],
            'conditions' => ['accepted'],
            ...(app(Captcha::class)->actif() ? ['captcha' => ['required', new CaptchaValide]] : []),
        ], [
            'captcha.required' => 'Recopiez les caractères de l\'image pour prouver que vous êtes humain.',
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.unique' => 'Un compte existe déjà avec cette adresse e-mail.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex' => 'Le mot de passe doit contenir une majuscule, une minuscule et un chiffre.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
            'conditions.accepted' => 'Vous devez accepter les conditions pour créer un compte.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Un nouveau compte est toujours client ; il confirme d'abord son adresse avec le code reçu par e-mail.
        return redirect()->route('verification.notice');
    }
}
