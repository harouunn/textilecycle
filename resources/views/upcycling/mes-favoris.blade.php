@extends('layouts.front')

@section('title', 'Mes projets favoris')

@push('styles')
  <link rel="stylesheet" href="{{ asset_v('front/css/upcycling-favorites.css') }}">
@endpush

@section('content')
@php
  // Compléter en deux lectures groupées les projets déjà sélectionnés et paginés.
  $projets->getCollection()->loadMissing('user')->loadCount('etapes');
@endphp
<section class="upcycling-favorites-page" aria-labelledby="ufp-title">
  <div class="container ufp-container">
    <header class="ufp-hero">
      <div class="ufp-hero-content">
        <p class="ufp-eyebrow"><span aria-hidden="true">♡</span> Ma collection d’inspirations</p>
        <h1 id="ufp-title" class="ufp-title">Mes projets <em>favoris</em></h1>
        <p class="ufp-intro">Un détail qui inspire, une idée à réinventer. Gardez vos coups de cœur à portée de main pour imaginer la seconde vie de vos vêtements.</p>
        <div class="ufp-header-actions">
          <a href="{{ route('upcycling.index') }}" class="ufp-button ufp-button-primary">Découvrir la galerie <span aria-hidden="true">↗</span></a>
          <span class="ufp-total"><strong>{{ $projets->total() }}</strong> {{ $projets->total() > 1 ? 'projets favoris' : 'projet favori' }}</span>
        </div>
      </div>
      <div class="ufp-textile-art" aria-hidden="true">
        <span class="ufp-swatch ufp-swatch-denim"></span>
        <span class="ufp-swatch ufp-swatch-clay"></span>
        <span class="ufp-swatch ufp-swatch-sage"><span>♡</span></span>
        <span class="ufp-art-label">De belles idées à garder près de soi.</span>
      </div>
    </header>

    <div class="ufp-messages">
      @include('upcycling.partials.flash', ['errors' => new \Illuminate\Support\ViewErrorBag])
      @if ($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Une action n’a pas pu aboutir :</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
      @endif
    </div>

    @if ($projets->isEmpty())
      <div class="ufp-empty">
        <div class="ufp-empty-art" aria-hidden="true"><span>♡</span></div>
        @if ($projets->total() === 0)
          <p class="ufp-eyebrow">Une place pour vos coups de cœur</p>
          <h2>Vous n’avez encore aucun projet favori.</h2>
          <p>Découvrez la galerie et enregistrez les projets qui vous inspirent.</p>
          <a href="{{ route('upcycling.index') }}" class="ufp-button ufp-button-primary">Découvrir la galerie <span aria-hidden="true">↗</span></a>
          <details class="ufp-empty-help">
            <summary>À savoir sur les projets enregistrés</summary>
            <p>Vous n’avez aucun projet publié dans vos favoris. Les projets retirés de la publication n’apparaissent plus dans cette liste.</p>
          </details>
        @else
          <h2>Aucun favori sur cette page.</h2>
          <p>Retrouvez votre collection en revenant à la première page.</p>
          <a href="{{ route('upcycling.mes-favoris') }}" class="ufp-button ufp-button-primary">Revenir à mes favoris</a>
        @endif
      </div>
    @else
      <div class="ufp-collection-heading">
        <div><p class="ufp-eyebrow">À transformer en nouvelles idées</p><h2>Vos inspirations</h2></div>
        <p>{{ $projets->firstItem() }}–{{ $projets->lastItem() }} sur {{ $projets->total() }} favori(s)</p>
      </div>
      <div class="ufp-grid">
        @foreach ($projets as $projet)
          @php
            $photo = $projet->photoApresUrl() ?: $projet->photoAvantUrl();
            $photoLibelle = $projet->photoApresUrl() ? 'Après transformation' : 'Avant transformation';
          @endphp
          <article class="ufp-card" aria-labelledby="ufp-projet-{{ $projet->id }}">
            <div class="ufp-card-visual">
              <a href="{{ route('upcycling.projets.show', $projet) }}" class="ufp-image-link" aria-label="Voir le projet : {{ $projet->titre }}">
                @if ($photo)
                  <img src="{{ $photo }}" alt="{{ $photoLibelle }} : {{ $projet->titre }}" class="ufp-cover" loading="lazy" width="640" height="400">
                @else
                  <div class="ufp-placeholder"><span class="ufp-placeholder-patch" aria-hidden="true">✳</span><span>Une idée à réinventer</span><small>Photo non disponible</small></div>
                @endif
              </a>
              <span class="ufp-favorite-label"><span aria-hidden="true">♡</span> Dans mes favoris</span>
            </div>
            <div class="ufp-card-body">
              <div class="ufp-card-meta">
                <span class="ufp-difficulty">{{ $projet->difficulte->label() }}</span>
                @if ($projet->created_at)
                  <span class="ufp-created">Créé le <time datetime="{{ $projet->created_at->toIso8601String() }}">{{ $projet->created_at->format('d/m/Y') }}</time></span>
                @endif
              </div>
              <h3 id="ufp-projet-{{ $projet->id }}"><a href="{{ route('upcycling.projets.show', $projet) }}">{{ $projet->titre }}</a></h3>
              <p class="ufp-author">Une création de <strong>{{ $projet->user?->name ?? 'la communauté TexTileCycle' }}</strong></p>
              <div class="ufp-transformation">
                <div><span>À partir de</span><p>{{ $projet->vetement_origine }}</p></div>
                <span class="ufp-transform-arrow" aria-hidden="true">↗</span>
                <div><span>Pour créer</span><p>{{ $projet->resultat }}</p></div>
              </div>
              <dl class="ufp-details">
                <div><dt>Durée</dt><dd>{{ $projet->dureeFormatee() }}</dd></div>
                <div><dt>Étapes</dt><dd>{{ $projet->etapes_count }}</dd></div>
              </dl>
              <div class="ufp-card-actions">
                <a href="{{ route('upcycling.projets.show', $projet) }}" class="ufp-button ufp-button-primary" aria-label="Voir le projet : {{ $projet->titre }}">Voir le projet <span aria-hidden="true">↗</span></a>
                <form method="POST" action="{{ route('upcycling.favoris.destroy', $projet) }}" class="ufp-remove-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="ufp-remove" aria-label="Retirer des favoris : {{ $projet->titre }}">Retirer des favoris</button>
                </form>
              </div>
            </div>
          </article>
        @endforeach
      </div>
      <nav class="ufp-pagination" aria-label="Pages de mes favoris">{{ $projets->links('pagination::bootstrap-5') }}</nav>
    @endif
  </div>
</section>
@endsection
