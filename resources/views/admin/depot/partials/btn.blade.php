{{--
  Bouton Vuetify statique.
  Paramètres : label, href (sinon <button>), type (submit), color (primary), variant (elevated|tonal|outlined|text),
  size (default|small), icon (classe boxicons), iconOnly (bool), title
--}}
@php
  $color = $color ?? 'primary';
  $variant = $variant ?? 'elevated';
  $size = $size ?? 'default';
  $iconOnly = $iconOnly ?? false;
  $classes = 'v-btn v-theme--light v-btn--density-default v-btn--size-'.$size.' v-btn--variant-'.$variant
    .($variant === 'elevated' ? ' v-btn--elevated bg-'.$color : ' text-'.$color)
    .($iconOnly ? ' v-btn--icon' : '');
@endphp
@isset($href)
  <a href="{{ $href }}" class="{{ $classes }}" @isset($title) title="{{ $title }}" aria-label="{{ $title }}" @endisset>
@else
  <button type="{{ $type ?? 'submit' }}" class="{{ $classes }}" @isset($title) title="{{ $title }}" aria-label="{{ $title }}" @endisset>
@endisset
    <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
    <span class="v-btn__content">
      @isset($icon)
        <i class="{{ $icon }} v-icon notranslate v-theme--light {{ $iconOnly ? '' : 'me-1' }}" aria-hidden="true" style="font-size: 20px; height: 20px; width: 20px;"></i>
      @endisset
      @unless ($iconOnly) {{ $label }} @endunless
    </span>
@isset($href)
  </a>
@else
  </button>
@endisset
