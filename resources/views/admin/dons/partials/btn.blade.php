{{--
  Sneat button.
  Paramètres : label, href (lien) ou type (bouton), color (défaut primary), variant (elevated|tonal|outlined|text),
  size (default|small), icon (classe boxicons), title, confirm (message de confirmation)
--}}
@php
  $color = $color ?? 'primary';
  $variant = $variant ?? 'elevated';
  $classes = 'v-btn v-theme--light v-btn--density-default v-btn--size-'.($size ?? 'default').' v-btn--variant-'.$variant
    .($variant === 'elevated' ? ' bg-'.$color : ' text-'.$color)
    .(empty($label) ? ' v-btn--icon' : '');
@endphp
@isset($href)
  <a href="{{ $href }}" class="{{ $classes }}" @isset($title) title="{{ $title }}" aria-label="{{ $title }}" @endisset>
@else
  <button type="{{ $type ?? 'submit' }}" class="{{ $classes }}" @isset($title) title="{{ $title }}" aria-label="{{ $title }}" @endisset
    @isset($confirm) onclick="return confirm(@js($confirm))" @endisset>
@endisset
    <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
    <span class="v-btn__content">
      @isset($icon)<i class="{{ $icon }} v-icon notranslate v-theme--light" aria-hidden="true" @unless (empty($label)) style="margin-inline-end:.375rem" @endunless></i>@endisset
      {{ $label ?? '' }}
    </span>
@isset($href)
  </a>
@else
  </button>
@endisset
