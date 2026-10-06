{{-- Carte vêtement du catalogue (markup « product-item » du template). Paramètre : $vetement --}}
<div class="product-item image-zoom-effect link-effect">
  <div class="image-holder position-relative">
    <a href="{{ route('depot.catalogue.show', $vetement) }}">
      <img src="{{ $vetement->photo_url }}" alt="{{ $vetement->titre }}" class="product-image img-fluid tc-vetement-image">
    </a>
    <div class="product-content">
      <h5 class="element-title text-uppercase fs-6 mt-3 mb-1">
        <a href="{{ route('depot.catalogue.show', $vetement) }}">{{ $vetement->titre }}</a>
      </h5>
      <p class="small mb-1">{{ $vetement->categorie->nom }} · Taille {{ $vetement->taille->label() }} · {{ $vetement->etat->label() }}</p>
      <a href="{{ route('depot.catalogue.show', $vetement) }}" class="text-decoration-none" data-after="Voir le détail"><span>{{ $vetement->genre->label() }}</span></a>
    </div>
  </div>
</div>
