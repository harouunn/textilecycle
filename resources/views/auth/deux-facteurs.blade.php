@extends('layouts.front')

@section('title', 'Vérification en deux étapes')

@section('content')
  <x-ui.auth surtitre="Vérification en deux étapes" titre="Code de sécurité"
    sous-titre="Ouvrez votre application d'authentification (Google Authenticator, Microsoft Authenticator…) et saisissez le code à 6 chiffres."
    image="banner-image-6.jpg" accroche="Votre compte est protégé par la double authentification.">
    <form method="POST" action="{{ route('deux-facteurs.store') }}" class="tc-form" novalidate>
      @csrf
      <div class="mb-4">
        <label for="code" class="form-label">Code à 6 chiffres</label>
        <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" autofocus placeholder="••••••"
          @class(['form-control tc-code', 'is-invalid' => $errors->has('code')])>
        @error('code')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <button type="submit" class="tc-btn tc-btn--bloc"><x-ui.icone nom="bouclier" /> Vérifier</button>
    </form>

    <div class="tc-separateur">ou</div>

    <details class="tc-repli" @if ($errors->has('code_secours')) open @endif>
      <summary>Téléphone perdu ? Utiliser un code de secours</summary>
      <form method="POST" action="{{ route('deux-facteurs.store') }}" class="tc-form mt-3" novalidate>
        @csrf
        <label for="code_secours" class="form-label">Code de secours</label>
        <div class="d-flex gap-2">
          <input id="code_secours" type="text" name="code_secours" autocomplete="off" placeholder="XXXX-XXXX"
            @class(['form-control', 'is-invalid' => $errors->has('code_secours')])>
          <button type="submit" class="tc-btn tc-btn--clair">Valider</button>
        </div>
        @error('code_secours')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text">Chaque code de secours ne fonctionne qu'une seule fois.</div>
      </form>
    </details>

    <p class="tc-auth__bas"><a href="{{ route('login') }}">Revenir à la connexion</a></p>
  </x-ui.auth>
@endsection
