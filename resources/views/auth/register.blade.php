@extends('layouts.front')

@section('title', 'Inscription')

@section('content')
  <x-ui.auth surtitre="Bienvenue" titre="Créer un compte" sous-titre="Rejoignez la communauté TexTileCycle en moins d'une minute."
    image="banner-image-4.jpg" accroche="Rejoignez le cycle : déposez, réparez, transformez, donnez.">
    <form method="POST" action="{{ route('register') }}" class="tc-form" novalidate>
      @csrf

      <div class="mb-3">
        <label for="name" class="form-label">Nom complet</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Prénom Nom"
          @class(['form-control', 'is-invalid' => $errors->has('name')])>
        @error('name')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="vous@exemple.com"
          @class(['form-control', 'is-invalid' => $errors->has('email')])>
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <x-ui.mot-de-passe id="password" label="Mot de passe" autocomplete="new-password" data-force="force-mdp" required>
        <div class="tc-force" id="force-mdp" data-niveau="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <ul class="tc-regles">
          <li data-regle="longueur">8 caractères minimum</li>
          <li data-regle="lettre">Au moins une lettre</li>
          <li data-regle="chiffre">Au moins un chiffre</li>
          <li data-regle="majuscule">Majuscules et minuscules</li>
        </ul>
      </x-ui.mot-de-passe>

      <x-ui.mot-de-passe id="password_confirmation" label="Confirmer le mot de passe" autocomplete="new-password" required />

      <x-ui.captcha />

      <label class="tc-check mb-1">
        <input type="checkbox" name="conditions" value="1" @checked(old('conditions')) required>
        <span>J'accepte que mes vêtements déposés soient vérifiés puis proposés à la communauté.</span>
      </label>
      @error('conditions')
        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
      @enderror

      <button type="submit" class="tc-btn tc-btn--vert tc-btn--bloc mt-3">Créer mon compte</button>
    </form>

    <p class="tc-auth__bas">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
  </x-ui.auth>
@endsection
