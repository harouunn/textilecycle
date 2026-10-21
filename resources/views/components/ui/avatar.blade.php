{{-- Photo de profil ronde, ou initiales si aucune photo. Usage : <x-ui.avatar :user="$user" taille="40" /> --}}
@props(['user', 'taille' => 40])
@php($t = (int) $taille)
@if ($url = $user->photoProfilUrl())
  <img src="{{ $url }}" alt="Photo de profil de {{ $user->name }}" width="{{ $t }}" height="{{ $t }}"
    {{ $attributes->merge(['class' => 'tc-avatar']) }} style="width: {{ $t }}px; height: {{ $t }}px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
@else
  <span {{ $attributes->merge(['class' => 'tc-avatar']) }} aria-hidden="true"
    style="width: {{ $t }}px; height: {{ $t }}px; border-radius: 50%; display: inline-grid; place-items: center; flex-shrink: 0;
      background: #e8f1e3; color: #2f5a45; font-weight: 600; font-size: {{ round($t * .38) }}px;">{{ $user->initiales() }}</span>
@endif
