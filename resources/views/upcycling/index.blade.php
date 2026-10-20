@extends('layouts.front')

@section('title', 'Upcycling : idées de transformation')

@section('content')
@include('upcycling.partials.styles')

<div class="tc-page">
  <x-ui.hero surtitre="Transformation" titre="Upcycling"
    sous-titre="Vieux jean, chemise usée, pull troué : découvrez des tutoriels pas à pas pour transformer vos vêtements en créations utiles et uniques."
    :fil="['Accueil' => route('home'), 'Upcycling' => null]">
    @auth
      <a href="{{ route('upcycling.projets.create') }}" class="tc-btn">Proposer un projet</a>
      <a href="{{ route('upcycling.mes-projets') }}" class="tc-btn tc-btn--clair">Mes projets</a>
    @else
      <a href="{{ route('login') }}" class="tc-btn tc-btn--clair">Connectez-vous pour proposer un projet</a>
    @endauth
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    @include('upcycling.partials.flash')

    <form method="GET" action="{{ route('upcycling.index') }}" class="border rounded p-4 mb-4">
      <h2 class="h5">Trouver une idée avec la recherche assistée</h2>
      <label for="recherche-idee">Décrivez votre vêtement et votre idée</label>
      <textarea id="recherche-idee" name="idee" maxlength="2000" class="form-control" placeholder="J’ai un vieux jean et je veux créer un sac facile.">{{ $idee }}</textarea>
      @if ($difficulteActive)<input type="hidden" name="difficulte" value="{{ $difficulteActive }}">@endif
      <button type="submit" class="btn btn-dark mt-2">Trouver des projets similaires</button>
      <a href="{{ route('upcycling.index') }}">Effacer la recherche</a>
      <p class="small mt-2">Compare votre idée aux tutoriels publiés. Fonctionne localement, sans clé API.</p>
      @error('idee')<p role="alert">{{ $message }}</p>@enderror
    </form>
    {{-- Filtre par difficulté --}}
    <div class="tcu-filtres d-flex flex-wrap justify-content-center gap-2 mb-5" role="group" aria-label="Filtrer par difficulté">
      <a href="{{ route('upcycling.index', ['idee' => $idee]) }}" @class(['btn', 'btn-dark' => ! $difficulteActive, 'btn-outline-dark' => $difficulteActive])>Tous</a>
      @foreach ($difficultes as $valeur => $libelle)
        <a href="{{ route('upcycling.index', ['difficulte' => $valeur, 'idee' => $idee]) }}"
          @class(['btn', 'btn-dark' => $difficulteActive === $valeur, 'btn-outline-dark' => $difficulteActive !== $valeur])>{{ $libelle }}</a>
      @endforeach
    </div>

    <div class="row g-4">
      @forelse ($projets as $projet)
        <div class="col-sm-6 col-lg-4">
          <article class="tcu-card h-100 d-flex flex-column">
            <a href="{{ route('upcycling.projets.show', $projet) }}">
              @if ($projet->photoApresUrl())
                <img src="{{ $projet->photoApresUrl() }}" alt="{{ $projet->resultat }}" class="tcu-cover">
              @else
                <div class="tcu-cover-vide">
                  <span class="text-uppercase small">{{ $projet->vetement_origine }}</span>
                  <span aria-hidden="true">↓</span>
                  <span class="text-uppercase small fw-bold">{{ $projet->resultat }}</span>
                </div>
              @endif
            </a>
            <div class="p-4 d-flex flex-column flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-2">
                @include('upcycling.partials.badge', ['enum' => $projet->difficulte])
                <span class="tcu-meta">{{ $projet->dureeFormatee() }} · {{ $projet->etapes_count }} étape(s)</span>
              </div>
              <h3 class="h5 text-uppercase mb-2">
                <a href="{{ route('upcycling.projets.show', $projet) }}" class="item-anchor text-decoration-none">{{ $projet->titre }}</a>
              </h3>
              <p class="mb-3">{{ Str::limit($projet->description, 110) }}</p>
              <p class="tcu-meta mt-auto mb-0">Proposé par {{ $projet->user->name }}</p>
            </div>
          </article>
        </div>
      @empty
        <div class="col-12 text-center py-5">
          <p class="mb-0">{{ $idee ? "Aucune correspondance. Essayez un vêtement, un résultat ou une difficulté différents." : "" }}</p><p class="mb-0" @if($idee) hidden @endif>Aucun projet {{ $difficulteActive ? 'de cette difficulté ' : '' }}n'est publié pour le moment.</p>
        </div>
      @endforelse
    </div>

    <div class="d-flex justify-content-center mt-5">
      {{ $projets->links('pagination::bootstrap-5') }}
    </div>
  </div>
</section>
@endsection
