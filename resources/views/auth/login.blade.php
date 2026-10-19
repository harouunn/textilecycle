@extends('layouts.front')

@section('title', 'Connexion')

@section('content')
  <x-ui.auth surtitre="Bon retour" titre="Connexion" sous-titre="Connectez-vous pour déposer, réserver et suivre vos vêtements."
    image="banner-image-2.jpg" accroche="Chaque vêtement mérite une seconde vie.">
    <form method="POST" action="{{ route('login') }}" class="tc-form" novalidate>
      @csrf

      <div class="mb-3">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="vous@exemple.com"
          @class(['form-control', 'is-invalid' => $errors->has('email')])>
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <x-ui.mot-de-passe id="password" label="Mot de passe" autocomplete="current-password" required />

      <x-ui.captcha />

      <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
        <label class="tc-check mb-0">
          <input id="remember_me" type="checkbox" name="remember"> Se souvenir de moi
        </label>
        @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="tc-lien small">Mot de passe oublié ?</a>
        @endif
      </div>

      <button type="submit" class="tc-btn tc-btn--bloc">Se connecter</button>
    </form>

    <div class="tc-separateur">Sécurité</div>
    <p class="small d-flex gap-2 mb-0">
      <x-ui.icone nom="bouclier" style="width: 20px; height: 20px; color: var(--tc-vert); flex-shrink: 0;" />
      Si vous avez activé la double authentification, votre application vous demandera ensuite un code à 6 chiffres.
    </p>

    @if (Route::has('register'))
      <p class="tc-auth__bas">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
    @endif
  </x-ui.auth>
@endsection
