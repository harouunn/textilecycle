@extends('layouts.front')

@section('title', 'Mot de passe oublié')

@section('content')
  <x-ui.auth surtitre="Mot de passe oublié" titre="Réinitialiser l'accès"
    sous-titre="Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe."
    image="banner-image-3.jpg" accroche="Pas de panique, on vous aide à retrouver votre compte.">
    <form method="POST" action="{{ route('password.email') }}" class="tc-form" novalidate>
      @csrf
      <div class="mb-4">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="vous@exemple.com"
          @class(['form-control', 'is-invalid' => $errors->has('email')])>
        @error('email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <button type="submit" class="tc-btn tc-btn--bloc"><x-ui.icone nom="enveloppe" /> Envoyer le lien</button>
    </form>
    <p class="tc-auth__bas"><a href="{{ route('login') }}">Retour à la connexion</a></p>
  </x-ui.auth>
@endsection
