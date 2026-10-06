{{--
  Lien du menu latéral.
  Paramètres : title, icon (classe boxicons), href (défaut "#"), active (bool), badge (texte optionnel)
--}}
@php($active = $active ?? false)
<li class="nav-link">
  <a href="{{ $href ?? '#' }}" @class(['router-link-active router-link-exact-active' => $active]) @if ($active) aria-current="page" @endif>
    <i class="{{ $icon }} v-icon notranslate v-theme--light v-icon--size-default nav-item-icon" aria-hidden="true"></i>
    <span class="nav-item-title">{{ $title }}</span>
    @isset($badge)
      <span class="nav-item-badge bg-light-primary text-primary">{{ $badge }}</span>
    @endisset
  </a>
</li>
