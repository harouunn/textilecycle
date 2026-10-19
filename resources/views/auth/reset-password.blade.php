@extends('layouts.front')

@section('title', 'Nouveau mot de passe')

@section('content')
  <x-ui.auth surtitre="Mot de passe oublié" titre="Nouveau mot de passe" sous-titre="Choisissez un nouveau mot de passe pour votre compte."
    image="banner-image-3.jpg" accroche="Un nouveau départ pour votre compte.">
    <form method="POST" action="{{ route('password.store') }}" class="tc-form" novalidate>
      @csrf
      <input type="hidden" name="token" value="{{ $request->route('token') }}">

      <div class="mb-3">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
          @class(['form-control', 'is-invalid' => $errors->has('email')])>
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <x-ui.mot-de-passe id="password" label="Nouveau mot de passe" autocomplete="new-password" data-force="force-mdp" required>
        <div class="tc-force" id="force-mdp" data-niveau="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <ul class="tc-regles">
          <li data-regle="longueur">8 caractères minimum</li>
          <li data-regle="lettre">Au moins une lettre</li>
          <li data-regle="chiffre">Au moins un chiffre</li>
          <li data-regle="majuscule">Majuscules et minuscules</li>
        </ul>
      </x-ui.mot-de-passe>
      <x-ui.mot-de-passe id="password_confirmation" label="Confirmer le mot de passe" autocomplete="new-password" required />

      <button type="submit" class="tc-btn tc-btn--bloc mt-2">Réinitialiser le mot de passe</button>
    </form>
  </x-ui.auth>
@endsection
