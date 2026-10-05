{{-- User form (create / edit). Paramètres : user, action, method --}}
<form method="POST" action="{{ $action }}" novalidate>
  @csrf
  @if (($method ?? 'POST') !== 'POST')
    @method($method)
  @endif

  <div class="v-row">
    <div class="v-col-md-6 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Identité</div></div></div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="name" class="tcd-label">Nom complet <span class="tcd-required">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" @class(['tcd-input', 'is-invalid' => $errors->has('name')]) required autocomplete="off">
            @error('name') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field mb-0">
            <label for="email" class="tcd-label">E-mail <span class="tcd-required">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" @class(['tcd-input', 'is-invalid' => $errors->has('email')]) required autocomplete="off">
            @error('email') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-md-6 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
        <div class="v-card-item">
          <div class="v-card-item__content">
            <div class="v-card-title">Mot de passe</div>
            @if ($user->exists)
              <div class="v-card-subtitle">Laissez vide pour conserver le mot de passe actuel.</div>
            @endif
          </div>
        </div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="password" class="tcd-label">Mot de passe @unless ($user->exists)<span class="tcd-required">*</span>@endunless</label>
            <input type="password" id="password" name="password" @class(['tcd-input', 'is-invalid' => $errors->has('password')]) autocomplete="new-password" @unless ($user->exists) required @endunless>
            <div class="tcd-hint">8 caractères minimum.</div>
            @error('password') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field mb-0">
            <label for="password_confirmation" class="tcd-label">Confirmer le mot de passe</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="tcd-input" autocomplete="new-password">
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-12 d-flex gap-3">
      @include('admin.dons.partials.btn', ['label' => $user->exists ? 'Enregistrer les modifications' : 'Créer le compte', 'icon' => 'bx-save'])
      @include('admin.dons.partials.btn', ['href' => $user->exists ? route('admin.users.show', $user) : route('admin.users.index'), 'label' => 'Annuler', 'variant' => 'tonal', 'color' => 'secondary'])
    </div>
  </div>
</form>
