@extends('layouts.front')

@section('title', 'Mes projets Upcycling')

@push('styles')
  <link rel="stylesheet" href="{{ asset_v('front/css/upcycling-my-projects.css') }}">
@endpush

@section('content')
@php
  $statuts = \App\Enums\Upcycling\StatutProjet::cases();
  $compteursPage = $projets->getCollection()->countBy(fn ($projet) => $projet->statut->value);
@endphp
<section class="upcycling-my-projects" aria-labelledby="upm-title">
  <div class="container upm-container">
    <header class="upm-hero">
      <div class="upm-hero-content">
        <p class="upm-eyebrow"><span aria-hidden="true">✳</span> Mon atelier créatif</p>
        <h1 id="upm-title" class="upm-title">Mes projets <em>Upcycling</em></h1>
        <p class="upm-intro">Une nouvelle idée, une seconde vie. Retrouvez vos créations, peaufinez vos étapes et partagez votre savoir-faire avec la communauté.</p>
        <div class="upm-header-actions">
          <a href="{{ route('upcycling.projets.create') }}" class="upm-button upm-button-primary"><span aria-hidden="true">＋</span> Proposer un projet</a>
          <a href="{{ route('upcycling.mes-favoris') }}" class="upm-button upm-button-quiet">Mes favoris <span aria-hidden="true">↗</span></a>
        </div>
        <p class="upm-hero-note">Chaque projet est publié après validation par l’équipe TexTileCycle.</p>
      </div>
      <div class="upm-textile-art" aria-hidden="true">
        <span class="upm-fabric upm-fabric-denim"></span>
        <span class="upm-fabric upm-fabric-clay"></span>
        <span class="upm-fabric upm-fabric-sage"></span>
        <span class="upm-art-label">Imaginer. Transformer. Transmettre.</span>
      </div>
    </header>

    <div class="upm-messages">
      @include('upcycling.partials.flash', ['errors' => new \Illuminate\Support\ViewErrorBag])
      @if ($errors->any())
        <div class="alert alert-danger" role="alert">
          <strong>Quelques éléments restent à corriger :</strong>
          <ul class="mb-0 mt-2">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
      @endif
    </div>

    @if ($projets->total() > 0)
      <div class="upm-overview" aria-label="Aperçu de vos projets">
        <div class="upm-stat upm-stat-total"><span class="upm-stat-number">{{ $projets->total() }}</span><span>Total de vos projets</span></div>
        @foreach ($statuts as $statut)
          <div class="upm-stat upm-stat-{{ $statut->value }}"><span class="upm-stat-number">{{ $compteursPage->get($statut->value, 0) }}</span><span>{{ ['brouillon' => 'Brouillons', 'soumis' => 'Soumis', 'publie' => 'Publiés', 'rejete' => 'Rejetés'][$statut->value] }}</span></div>
        @endforeach
      </div>
      <p class="upm-stats-note">Les compteurs par statut concernent les projets affichés sur cette page.</p>
    @endif

    @if ($projets->isEmpty())
      <div class="upm-empty">
        <div class="upm-empty-art" aria-hidden="true"><span>＋</span></div>
        @if ($projets->total() === 0)
          <p class="upm-eyebrow">Tout commence par une idée</p>
          <h2>Votre prochaine création commence ici.</h2>
          <p>Un jean oublié, une chemise délaissée… Donnez-leur une seconde vie et racontez leur transformation, étape par étape.</p>
          <a href="{{ route('upcycling.projets.create') }}" class="upm-button upm-button-primary">Créer mon premier projet <span aria-hidden="true">↗</span></a>
        @else
          <h2>Aucun projet sur cette page.</h2>
          <p>Retrouvez vos créations en revenant à la première page de votre atelier.</p>
          <a href="{{ route('upcycling.mes-projets') }}" class="upm-button upm-button-primary">Revenir à mes projets</a>
        @endif
      </div>
    @else
      <div class="upm-collection-heading">
        <div><p class="upm-eyebrow">Des idées qui prennent forme</p><h2>Vos créations</h2></div>
        <p>{{ $projets->firstItem() }}–{{ $projets->lastItem() }} sur {{ $projets->total() }} projet(s)</p>
      </div>
      <div class="upm-grid">
        @foreach ($projets as $projet)
          @php
            $photo = $projet->photoApresUrl() ?: $projet->photoAvantUrl();
            $photoLibelle = $projet->photoApresUrl() ? 'Après transformation' : 'Avant transformation';
          @endphp
          <article class="upm-card" aria-labelledby="upm-projet-{{ $projet->id }}">
            <div class="upm-card-visual">
              <a href="{{ route('upcycling.projets.show', $projet) }}" class="upm-image-link" aria-label="Aperçu : {{ $projet->titre }}">
                @if ($photo)
                  <img src="{{ $photo }}" alt="{{ $photoLibelle }} : {{ $projet->titre }}" class="upm-cover" loading="lazy" width="640" height="400">
                @else
                  <div class="upm-placeholder"><span class="upm-placeholder-patch" aria-hidden="true">✳</span><span>Une création en devenir</span><small>Photo à ajouter</small></div>
                @endif
              </a>
              <span class="upm-status upm-status-{{ $projet->statut->value }}"><span aria-hidden="true">{{ ['brouillon' => '✎', 'soumis' => '◷', 'publie' => '✓', 'rejete' => '!'][$projet->statut->value] }}</span> {{ $projet->statut->label() }}</span>
            </div>
            <div class="upm-card-body">
              <p class="upm-created">Créé le <time datetime="{{ $projet->created_at?->toIso8601String() }}">{{ $projet->created_at?->format('d/m/Y') ?? '—' }}</time></p>
              <h3 id="upm-projet-{{ $projet->id }}"><a href="{{ route('upcycling.projets.show', $projet) }}">{{ $projet->titre }}</a></h3>
              <div class="upm-transformation">
                <div><span>À partir de</span><p>{{ $projet->vetement_origine }}</p></div>
                <span class="upm-transform-arrow" aria-hidden="true">↗</span>
                <div><span>Pour créer</span><p>{{ $projet->resultat }}</p></div>
              </div>
              <dl class="upm-details">
                <div><dt>Difficulté</dt><dd>{{ $projet->difficulte->label() }}</dd></div>
                <div><dt>Durée</dt><dd>{{ $projet->dureeFormatee() }}</dd></div>
                <div><dt>Étapes</dt><dd>{{ $projet->etapes_count }}</dd></div>
              </dl>
              <div class="upm-moderation">
                @include('upcycling.partials.moderation', ['errors' => new \Illuminate\Support\ViewErrorBag])
              </div>
              <div class="upm-card-actions">
                <a href="{{ route('upcycling.projets.show', $projet) }}" class="upm-button upm-button-preview" aria-label="Aperçu : {{ $projet->titre }}">Aperçu <span aria-hidden="true">↗</span></a>
                <a href="{{ route('upcycling.projets.edit', $projet) }}" class="upm-button upm-button-secondary" aria-label="Modifier : {{ $projet->titre }}">Modifier</a>
                <a href="{{ route('upcycling.projets.etapes.index', $projet) }}" class="upm-steps" aria-label="Gérer les étapes : {{ $projet->titre }}">Gérer les étapes <span aria-hidden="true">→</span></a>
                <form method="POST" action="{{ route('upcycling.projets.destroy', $projet) }}" class="upm-delete-form"
                  onsubmit="return confirm('Supprimer définitivement ce projet et toutes ses étapes ?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="upm-delete" aria-label="Supprimer : {{ $projet->titre }}">Supprimer</button>
                </form>
              </div>
            </div>
          </article>
        @endforeach
      </div>
      <nav class="upm-pagination" aria-label="Pages de mes projets">
        {{ $projets->links('pagination::bootstrap-5') }}
      </nav>
    @endif
  </div>
</section>
@endsection
