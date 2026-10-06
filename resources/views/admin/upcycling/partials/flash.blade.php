@php
    $validationBag = request()->routeIs('*.etapes.*', 'admin.upcycling.projets.show') ? 'etape' : (request()->routeIs('*.projets.create', '*.projets.edit', 'upcycling.create', 'upcycling.edit') ? 'projet' : 'default');
    $validationErrors = $errors->getBag($validationBag);
@endphp
{{-- Messages flash et récapitulatif des erreurs de validation --}}
@foreach (['success' => ['success', 'bx-check-circle'], 'error' => ['error', 'bx-error-circle']] as $cle => [$couleur, $icone])
  @if (session($cle))
    <div class="v-alert v-theme--light text-{{ $couleur }} v-alert--density-default v-alert--variant-tonal mb-6" role="alert">
      <span class="v-alert__underlay"></span>
      <div class="v-alert__prepend">
        <i class="{{ $icone }} v-icon notranslate v-theme--light v-icon--size-default" aria-hidden="true"></i>
      </div>
      <div class="v-alert__content">{{ session($cle) }}</div>
    </div>
  @endif
@endforeach

@if ($validationErrors->any())
  <div class="v-alert v-theme--light text-error v-alert--density-default v-alert--variant-tonal mb-6" role="alert">
    <span class="v-alert__underlay"></span>
    <div class="v-alert__prepend">
      <i class="bx-error-circle v-icon notranslate v-theme--light v-icon--size-default" aria-hidden="true"></i>
    </div>
    <div class="v-alert__content">Le formulaire contient {{ $validationErrors->count() }} erreur(s). Corrigez les champs signalés ci-dessous.</div>
  </div>
@endif
