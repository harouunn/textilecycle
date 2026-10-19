{{--
  En-tête des pages du module.
  Paramètres : titre, sousTitre (facultatif), surtitre (facultatif), fil (tableau libellé => URL, la dernière entrée sans URL).
  Le contenu du composant (slot) affiche les boutons d'action à droite.
--}}
@props(['titre', 'sousTitre' => null, 'surtitre' => null, 'fil' => []])
<header class="tc-hero">
  <div class="container">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-4">
      <div>
        @if ($fil)
          <nav class="tc-fil" aria-label="Fil d'Ariane">
            @foreach ($fil as $libelle => $url)
              @if ($url)
                <a href="{{ $url }}">{{ $libelle }}</a> <span aria-hidden="true">/</span>
              @else
                <span aria-current="page">{{ $libelle }}</span>
              @endif
            @endforeach
          </nav>
        @endif
        @if ($surtitre)
          <div class="tc-surtitre">{{ $surtitre }}</div>
        @endif
        <h1>{{ $titre }}</h1>
        @if ($sousTitre)
          <p>{{ $sousTitre }}</p>
        @endif
      </div>
      @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">{{ $slot }}</div>
      @endif
    </div>
  </div>
</header>
