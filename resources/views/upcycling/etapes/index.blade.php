@extends('layouts.front')

@section('title', 'Étapes du projet')

@section('content')
@include('upcycling.partials.styles')

<section class="bg-light py-5">
  <div class="container py-md-4">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <a href="{{ route('upcycling.mes-projets') }}" class="item-anchor small text-uppercase">← Mes projets</a>
        <h1 class="section-title mt-3 mb-1">Étapes du projet</h1>
        <p class="mb-4">
          {{ $projet->titre }} ·
          <a href="{{ route('upcycling.projets.show', $projet) }}" class="item-anchor">{{ $projet->estPublie() ? 'Voir la page publique' : 'Aperçu' }}</a> ·
          <a href="{{ route('upcycling.projets.edit', $projet) }}" class="item-anchor">Modifier le projet</a>
        </p>

        @include('upcycling.partials.flash')

        <div class="bg-white border mb-4">
          @forelse ($projet->etapes as $etape)
            <div class="d-flex gap-3 p-4 border-bottom">
              <div class="tcu-etape-num">{{ $etape->numero }}</div>
              <div class="flex-grow-1">
                <h2 class="h6 text-uppercase mb-1">{{ $etape->titre }}</h2>
                <p class="tcu-pre mb-0">{{ $etape->contenu }}</p>
              </div>
              @if ($etape->photoUrl())
                <img src="{{ $etape->photoUrl() }}" alt="Photo de l'étape {{ $etape->numero }}" class="tcu-thumb flex-shrink-0">
              @endif
              <div class="d-flex flex-column gap-2 flex-shrink-0">
                <a href="{{ route('upcycling.projets.etapes.edit', [$projet, $etape]) }}" class="btn btn-sm btn-outline-dark">Modifier</a>
                <form method="POST" action="{{ route('upcycling.projets.etapes.destroy', [$projet, $etape]) }}"
                  onsubmit="return confirm('Supprimer cette étape ?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger w-100">Supprimer</button>
                </form>
              </div>
            </div>
          @empty
            <p class="p-4 mb-0">Aucune étape pour le moment. Ajoutez la première ci-dessous.</p>
          @endforelse
        </div>

        <form method="POST" action="{{ route('upcycling.projets.etapes.store', $projet) }}" enctype="multipart/form-data" class="tcu-form bg-white border p-4 p-md-5" data-upcycling-form>
          @csrf
          <h2 class="h5 text-uppercase mb-4">Ajouter une étape</h2>
          @include('upcycling.partials.form-etape', ['etape' => null, 'numeroParDefaut' => $prochainNumero])
          <button type="submit" class="btn btn-dark text-uppercase mt-4">Ajouter l'étape</button>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
