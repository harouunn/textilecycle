{{-- Carte d'un vêtement du catalogue. Paramètre : vetement (avec sa catégorie chargée) --}}
@props(['vetement'])
<a href="{{ route('depot.catalogue.show', $vetement) }}" class="tc-produit">
  <div class="tc-produit__image">
    <img src="{{ $vetement->miniature_url }}" alt="{{ $vetement->titre }}" loading="lazy" decoding="async" width="480" height="600">
    <span class="tc-produit__cat tc-pastille">
      <i style="background: {{ $vetement->categorie->couleur }}"></i>{{ $vetement->categorie->nom }}
    </span>
  </div>
  <div class="tc-produit__corps">
    <h3 class="tc-produit__titre">{{ $vetement->titre }}</h3>
    <div class="tc-produit__meta mb-2">Taille {{ $vetement->taille->label() }} · {{ $vetement->genre->label() }} · {{ $vetement->etat->label() }}</div>
    <span class="tc-produit__cta">Voir le vêtement</span>
  </div>
</a>
