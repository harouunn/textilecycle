{{-- État vide d'une liste. Paramètres : icone, titre ; texte et bouton éventuel dans le slot --}}
@props(['icone' => 'colis', 'titre'])
<div class="tc-vide">
  <x-ui.icone :nom="$icone" />
  <h3>{{ $titre }}</h3>
  <div class="mt-2">{{ $slot }}</div>
</div>
