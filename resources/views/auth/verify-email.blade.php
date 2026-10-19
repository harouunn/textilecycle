@extends('layouts.front')

@section('title', 'Vérification de l\'adresse e-mail')

@section('content')
  <x-ui.auth surtitre="Dernière étape" titre="Vérifiez votre e-mail"
    :sous-titre="'Nous avons envoyé un code à 6 chiffres à '.Auth::user()->email.'. Saisissez-le pour activer votre compte.'"
    image="banner-image-4.jpg" accroche="Plus qu'un code avant de rejoindre le cycle.">
    @if ($codeDev)
      <div class="tc-alerte tc-alerte--info" role="status">
        <x-ui.icone nom="info" />
        <div>Mode développement (aucun serveur mail configuré) : votre code est <strong>{{ $codeDev }}</strong>.</div>
      </div>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}" class="tc-form" novalidate>
      @csrf
      <div class="mb-4">
        <label for="code" class="form-label">Code de vérification</label>
        <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" autofocus placeholder="••••••"
          @class(['form-control tc-code', 'is-invalid' => $errors->has('code')])>
        @error('code')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Le code est valable {{ \App\Services\Auth\VerificationEmail::DUREE_MINUTES }} minutes. Pensez à regarder dans les courriers indésirables.</div>
      </div>
      <button type="submit" class="tc-btn tc-btn--vert tc-btn--bloc"><x-ui.icone nom="valide" /> Confirmer mon adresse</button>
    </form>

    <div class="tc-separateur">Pas reçu ?</div>
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="tc-btn tc-btn--clair tc-btn--petit"><x-ui.icone nom="enveloppe" /> Renvoyer un code</button>
      </form>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="tc-btn tc-btn--clair tc-btn--petit">Se déconnecter</button>
      </form>
    </div>
  </x-ui.auth>
@endsection
