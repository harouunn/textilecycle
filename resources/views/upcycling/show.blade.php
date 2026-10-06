@extends('layouts.front')

@section('title', $projet->titre)

@section('content')
@include('upcycling.partials.styles')

<section class="bg-light py-5">
  <div class="container">
    <a href="{{ route('upcycling.index') }}" class="item-anchor small text-uppercase">← Tous les projets</a>

    @unless ($projet->estPublie())
      <div class="alert alert-warning mt-3 mb-0">Aperçu : ce projet est en brouillon et n'est visible que par vous. Il sera publié après validation par l'équipe TexTileCycle.</div>
    @endunless

    <div class="row mt-3 g-4 align-items-center">
      <div class="col-lg-7">
        <h1 class="section-title mb-3">{{ $projet->titre }}</h1>
        <p class="tcu-meta mb-3">
          @include('upcycling.partials.badge', ['enum' => $projet->difficulte])
          <span class="ms-2">Durée : {{ $projet->dureeFormatee() }}</span>
          <span class="ms-2">· {{ $projet->etapes->count() }} étape(s)</span>
          <span class="ms-2">· Proposé par {{ $projet->user->name }}</span>
        </p>
        <p class="tcu-pre mb-0">{{ $projet->description }}</p>
      </div>
      <div class="col-lg-5">
        <div class="tcu-card p-4">
          <p class="mb-1 text-uppercase small">Vêtement d'origine</p>
          <p class="fw-bold mb-3">{{ $projet->vetement_origine }}</p>
          <p class="mb-1 text-uppercase small">Résultat</p>
          <p class="fw-bold mb-3">{{ $projet->resultat }}</p>
          <p class="mb-1 text-uppercase small">Matériel nécessaire</p>
          <ul class="mb-0 ps-3">
            @foreach (preg_split('/\R/', $projet->materiel_necessaire, -1, PREG_SPLIT_NO_EMPTY) as $materiel)
              <li>{{ $materiel }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- Avant / après --}}
<section class="py-5">
  <div class="container">
    <h2 class="h4 text-uppercase text-center mb-4">Avant / après</h2>
    <div class="row g-4">
      @foreach (['Avant' => [$projet->photoAvantUrl(), $projet->vetement_origine], 'Après' => [$projet->photoApresUrl(), $projet->resultat]] as $libelle => [$url, $legende])
        <div class="col-md-6">
          <figure class="position-relative mb-0">
            @if ($url)
              <img src="{{ $url }}" alt="{{ $libelle }} : {{ $legende }}" class="tcu-cover">
            @else
              <div class="tcu-cover-vide">Photo non disponible</div>
            @endif
            <span class="tcu-photo-label">{{ $libelle }}</span>
            <figcaption class="tcu-meta mt-2 text-center">{{ ucfirst($legende) }}</figcaption>
          </figure>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- Étapes --}}
<section class="pb-5">
  <div class="container">
    <h2 class="h4 text-uppercase text-center mb-4">Étapes de réalisation</h2>
    <div class="row justify-content-center">
      <div class="col-lg-9">
        @forelse ($projet->etapes as $etape)
          <div class="d-flex gap-4 py-4 border-bottom">
            <div class="tcu-etape-num">{{ $etape->numero }}</div>
            <div class="flex-grow-1">
              <h3 class="h5 text-uppercase mb-2">{{ $etape->titre }}</h3>
              <p class="tcu-pre mb-0">{{ $etape->contenu }}</p>
              @if ($etape->photoUrl())
                <img src="{{ $etape->photoUrl() }}" alt="Étape {{ $etape->numero }} : {{ $etape->titre }}" class="tcu-etape-photo mt-3">
              @endif
            </div>
          </div>
        @empty
          <p class="text-center">Les étapes de ce projet n'ont pas encore été ajoutées.</p>
        @endforelse
      </div>
    </div>

    @can('update', $projet)
      <div class="text-center mt-5">
        <a href="{{ route('upcycling.projets.edit', $projet) }}" class="btn btn-outline-dark text-uppercase me-2">Modifier le projet</a>
        <a href="{{ route('upcycling.projets.etapes.index', $projet) }}" class="btn btn-dark text-uppercase">Gérer les étapes</a>
      </div>
    @endcan
  </div>
</section>
@endsection
