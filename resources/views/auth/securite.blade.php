@extends('layouts.front')

@section('title', 'Sécurité du compte')

@section('content')
  @php
    $active = $user->aDoubleAuthentification();
  @endphp

  <div class="tc-page">
    <x-ui.hero surtitre="Mon compte" titre="Sécurité du compte"
      sous-titre="Protégez votre compte avec la double authentification : en plus du mot de passe, un code à 6 chiffres généré par votre téléphone est demandé à chaque connexion."
      :fil="['Accueil' => route('home'), 'Sécurité du compte' => null]" />

    <section class="py-5">
      <div class="container" style="max-width: 980px;">
        @include('depot.partials.flash')

        {{-- État actuel --}}
        <div class="tc-carte tc-etat-securite mb-4">
          <div class="tc-etat-securite__icone" style="background: {{ $active ? 'var(--tc-ok-fond)' : 'var(--tc-attente-fond)' }}; color: {{ $active ? 'var(--tc-ok)' : 'var(--tc-attente)' }};">
            <x-ui.icone nom="bouclier" />
          </div>
          <div class="flex-grow-1">
            <h2 class="fs-5 mb-1">Double authentification
              <x-ui.badge :couleur="$active ? 'success' : ($enAttente ? 'warning' : 'secondary')" class="ms-2">{{ $active ? 'Activée' : ($enAttente ? 'À confirmer' : 'Désactivée') }}</x-ui.badge>
            </h2>
            <p class="mb-0 small">
              @if ($active)
                Activée le {{ $user->two_factor_confirmed_at->format('d/m/Y à H:i') }}. Il vous reste {{ count($user->two_factor_recovery_codes ?? []) }} code(s) de secours.
              @else
                Votre compte n'est protégé que par votre mot de passe.
              @endif
            </p>
          </div>
          @if (! $active && ! $enAttente)
            <form method="POST" action="{{ route('securite.activer') }}">
              @csrf
              <button type="submit" class="tc-btn tc-btn--vert"><x-ui.icone nom="qr" /> Activer</button>
            </form>
          @endif
        </div>

        {{-- Mise en place : scanner le QR code puis confirmer --}}
        @if ($enAttente)
          <div class="tc-carte">
            <div class="tc-carte__entete"><h2>Configurer votre application</h2></div>
            <div class="tc-carte__corps">
              <div class="row g-5 align-items-center">
                <div class="col-md-5 text-center">
                  <div class="tc-qr" aria-label="QR code à scanner">{!! $qrCode !!}</div>
                  <div class="small mt-3">Impossible de scanner ? Saisissez cette clé :</div>
                  <div class="tc-secret mt-1">{{ chunk_split($user->two_factor_secret, 4, ' ') }}</div>
                </div>
                <div class="col-md-7">
                  <ol class="tc-numeros mb-4">
                    <li><strong>Installez une application</strong> Google Authenticator, Microsoft Authenticator ou Authy sur votre téléphone.</li>
                    <li><strong>Scannez le QR code</strong> Dans l'application : « Ajouter un compte » puis « Scanner un QR code ».</li>
                    <li><strong>Saisissez le code affiché</strong> Il change toutes les 30 secondes.</li>
                  </ol>
                  <form method="POST" action="{{ route('securite.confirmer') }}" class="tc-form" novalidate>
                    @csrf
                    <label for="code" class="form-label">Code à 6 chiffres</label>
                    <div class="d-flex gap-2">
                      <input id="code" type="text" name="code" inputmode="numeric" maxlength="7" autocomplete="one-time-code" placeholder="••••••"
                        @class(['form-control tc-code', 'is-invalid' => $errors->has('code')]) style="max-width: 220px;">
                      <button type="submit" class="tc-btn tc-btn--vert">Confirmer</button>
                    </div>
                    @error('code')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </form>
                </div>
              </div>
            </div>
          </div>
        @endif

        {{-- Codes de secours, affichés une seule fois --}}
        @if ($codesSecours)
          <div class="tc-carte mb-4">
            <div class="tc-carte__entete"><h2>Vos codes de secours</h2></div>
            <div class="tc-carte__corps">
              <div class="tc-alerte tc-alerte--info"><x-ui.icone nom="info" /><div>Notez-les ou imprimez-les maintenant : ils ne seront plus affichés. Chacun permet une connexion si vous perdez votre téléphone.</div></div>
              <ul class="tc-codes">
                @foreach ($codesSecours as $code)
                  <li>{{ $code }}</li>
                @endforeach
              </ul>
            </div>
          </div>
        @endif

        {{-- Gestion quand la 2FA est active --}}
        @if ($active)
          <div class="row g-4">
            <div class="col-md-6">
              <div class="tc-carte tc-carte__corps h-100">
                <h3 class="fs-6 text-uppercase mb-2" style="letter-spacing: .08em;">Nouveaux codes de secours</h3>
                <p class="small">Générez une nouvelle liste si vous avez utilisé ou perdu vos codes. Les anciens ne fonctionneront plus.</p>
                <form method="POST" action="{{ route('securite.codes') }}">
                  @csrf
                  <button type="submit" class="tc-btn tc-btn--clair"><x-ui.icone nom="cle" /> Régénérer les codes</button>
                </form>
              </div>
            </div>
            <div class="col-md-6">
              <div class="tc-carte tc-carte__corps h-100">
                <h3 class="fs-6 text-uppercase mb-2" style="letter-spacing: .08em;">Désactiver</h3>
                <p class="small">Votre compte ne sera plus protégé que par le mot de passe.</p>
                <form method="POST" action="{{ route('securite.desactiver') }}" class="tc-form" novalidate>
                  @csrf
                  @method('DELETE')
                  <x-ui.mot-de-passe id="password" label="Mot de passe actuel" autocomplete="current-password" required />
                  <button type="submit" class="tc-btn tc-btn--danger">Désactiver la double authentification</button>
                </form>
              </div>
            </div>
          </div>
        @endif
      </div>
    </section>
  </div>
@endsection
