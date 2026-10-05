{{-- Flash messages (success / error) and a summary when the form has validation errors --}}
@foreach (['success' => 'success', 'error' => 'error'] as $key => $color)
  @if (session($key))
    <div class="v-alert v-theme--light text-{{ $color }} v-alert--density-default v-alert--variant-tonal rounded mb-6" role="alert">
      <span class="v-alert__underlay"></span>
      <div class="v-alert__content d-flex align-center gap-2">
        <i class="{{ $color === 'success' ? 'bx-check-circle' : 'bx-error-circle' }} v-icon notranslate v-theme--light" aria-hidden="true"></i>
        {{ session($key) }}
      </div>
    </div>
  @endif
@endforeach

@if ($errors->any())
  <div class="v-alert v-theme--light text-error v-alert--density-default v-alert--variant-tonal rounded mb-6" role="alert">
    <span class="v-alert__underlay"></span>
    <div class="v-alert__content">
      <strong>Le formulaire contient {{ $errors->count() }} erreur(s).</strong> Veuillez corriger les champs signalés.
    </div>
  </div>
@endif
