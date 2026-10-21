@extends('layouts.front')

@section('title', 'Mon profil')

@section('content')
  @php
    $espace = [
        ['Mes dépôts', $user->vetements()->count(), 'cintre', route('depot.mes-depots.index')],
        ['Mes réservations', $user->reservations()->count(), 'calendrier', route('depot.reservations.index')],
        ['Mes demandes de réparation', $user->demandesReparation()->count(), 'cle', route('ateliers.demandes.index')],
        ['Mes dons', $user->dons()->count(), 'feuille', route('dons.mes-dons')],
    ];
    $messages = ['profile-updated' => 'Vos informations ont été enregistrées.', 'password-updated' => 'Votre mot de passe a été modifié.'];
  @endphp

  <div class="tc-page">
    <x-ui.hero surtitre="Mon compte" :titre="'Bonjour '.$user->name" sous-titre="Gérez vos informations, votre mot de passe et la sécurité de votre compte."
      :fil="['Accueil' => route('home'), 'Mon profil' => null]">
      @if ($user->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="tc-btn">Tableau de bord</a>
      @endif
    </x-ui.hero>

    <section class="py-5">
      <div class="container" style="max-width: 1040px;">
        @if (isset($messages[session('status')]))
          <div class="tc-alerte tc-alerte--succes" role="status"><x-ui.icone nom="valide" /><div>{{ $messages[session('status')] }}</div></div>
        @endif

        {{-- Mon espace : accès rapide aux quatre gestions --}}
        <div class="row g-3 mb-5">
          @foreach ($espace as [$libelle, $nombre, $icone, $lien])
            <div class="col-6 col-lg-3">
              <a href="{{ $lien }}" class="tc-carte tc-stat h-100 text-decoration-none">
                <div class="tc-stat__icone"><x-ui.icone :nom="$icone" /></div>
                <div>
                  <div class="tc-stat__valeur">{{ $nombre }}</div>
                  <div class="tc-stat__label">{{ $libelle }}</div>
                </div>
              </a>
            </div>
          @endforeach
        </div>

        <div class="row g-4">
          {{-- Informations --}}
          <div class="col-lg-6">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="tc-carte tc-form h-100" novalidate>
              @csrf
              @method('PATCH')
              <div class="tc-carte__entete"><h2>Informations</h2></div>
              <div class="tc-carte__corps">
                {{-- Photo de profil : aperçu, nouvelle image ou retrait --}}
                <div class="d-flex align-items-center gap-3 mb-4">
                  <x-ui.avatar :user="$user" taille="72" />
                  <div class="flex-grow-1">
                    <label for="photo_profil" class="form-label mb-1">Photo de profil</label>
                    <input id="photo_profil" type="file" name="photo_profil" accept="image/jpeg,image/png,image/webp"
                      @class(['form-control', 'is-invalid' => $errors->has('photo_profil')])>
                    @error('photo_profil') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">JPG, PNG ou WebP, 2 Mo maximum.</div>
                    @if ($user->photo_profil)
                      <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="supprimer_photo" value="1" id="supprimer_photo">
                        <label class="form-check-label" for="supprimer_photo">Retirer ma photo</label>
                      </div>
                    @endif
                  </div>
                </div>
                <div class="mb-3">
                  <label for="name" class="form-label">Nom complet</label>
                  <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                    @class(['form-control', 'is-invalid' => $errors->has('name')])>
                  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                  <label for="email" class="form-label">Adresse e-mail</label>
                  <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                    @class(['form-control', 'is-invalid' => $errors->has('email')])>
                  @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  <div class="form-text">
                    @if ($user->hasVerifiedEmail())
                      <span style="color: var(--tc-ok);">● Adresse vérifiée.</span> Si vous la changez, un nouveau code de vérification vous sera envoyé.
                    @else
                      <span style="color: var(--tc-attente);">● Adresse non vérifiée.</span> <a href="{{ route('verification.notice') }}" class="tc-lien">Saisir mon code</a>
                    @endif
                  </div>
                </div>
                <button type="submit" class="tc-btn">Enregistrer</button>
              </div>
            </form>
          </div>

          {{-- Mot de passe --}}
          <div class="col-lg-6">
            <form method="POST" action="{{ route('password.update') }}" class="tc-carte tc-form h-100" novalidate>
              @csrf
              @method('PUT')
              <div class="tc-carte__entete"><h2>Mot de passe</h2></div>
              <div class="tc-carte__corps">
                @php($erreursMdp = $errors->getBag('updatePassword'))
                @foreach (['current_password' => ['Mot de passe actuel', 'current-password'], 'password' => ['Nouveau mot de passe', 'new-password'], 'password_confirmation' => ['Confirmer le nouveau mot de passe', 'new-password']] as $champ => [$libelle, $autocomplete])
                  <div class="mb-3">
                    <label for="mdp_{{ $champ }}" class="form-label">{{ $libelle }}</label>
                    <input id="mdp_{{ $champ }}" type="password" name="{{ $champ }}" autocomplete="{{ $autocomplete }}"
                      @class(['form-control', 'is-invalid' => $erreursMdp->has($champ)])>
                    @if ($erreursMdp->has($champ)) <div class="invalid-feedback">{{ $erreursMdp->first($champ) }}</div> @endif
                  </div>
                @endforeach
                <button type="submit" class="tc-btn mt-2">Modifier le mot de passe</button>
              </div>
            </form>
          </div>

          {{-- Sécurité --}}
          <div class="col-lg-6">
            <div class="tc-carte tc-carte__corps h-100">
              <h2 class="fs-6 text-uppercase mb-2" style="letter-spacing: .08em;">Double authentification</h2>
              <p class="small">
                @if ($user->aDoubleAuthentification())
                  <span style="color: var(--tc-ok);">● Activée.</span> Un code de votre téléphone est demandé à chaque connexion.
                @else
                  Protégez votre compte avec un code à 6 chiffres généré par votre téléphone (QR code à scanner).
                @endif
              </p>
              <a href="{{ route('securite.show') }}" class="tc-btn tc-btn--clair"><x-ui.icone nom="bouclier" /> Sécurité du compte</a>
            </div>
          </div>

          {{-- Suppression du compte --}}
          <div class="col-lg-6">
            <form method="POST" action="{{ route('profile.destroy') }}" class="tc-carte tc-form tc-carte__corps h-100" novalidate
              onsubmit="return confirm('Supprimer définitivement votre compte et toutes ses données ?')">
              @csrf
              @method('DELETE')
              <h2 class="fs-6 text-uppercase mb-2" style="letter-spacing: .08em; color: var(--tc-erreur);">Supprimer mon compte</h2>
              <p class="small">Vos dépôts, réservations et dons seront définitivement supprimés.</p>
              @php($erreursSuppression = $errors->getBag('userDeletion'))
              <label for="suppression_password" class="form-label">Mot de passe</label>
              <div class="d-flex gap-2">
                <input id="suppression_password" type="password" name="password" autocomplete="current-password"
                  @class(['form-control', 'is-invalid' => $erreursSuppression->has('password')])>
                <button type="submit" class="tc-btn tc-btn--danger">Supprimer</button>
              </div>
              @if ($erreursSuppression->has('password')) <div class="invalid-feedback d-block">{{ $erreursSuppression->first('password') }}</div> @endif
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
@endsection
