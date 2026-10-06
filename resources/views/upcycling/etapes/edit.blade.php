@extends('layouts.front')

@section('title', 'Modifier une étape')

@section('content')
@include('upcycling.partials.styles')

<section class="bg-light py-5">
  <div class="container py-md-4">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <a href="{{ route('upcycling.projets.etapes.index', $projet) }}" class="item-anchor small text-uppercase">← Étapes du projet</a>
        <h1 class="section-title mt-3 mb-1">Modifier l'étape n°{{ $etape->numero }}</h1>
        <p class="mb-4">{{ $projet->titre }}</p>

        @include('upcycling.partials.flash')

        <form method="POST" action="{{ route('upcycling.projets.etapes.update', [$projet, $etape]) }}" enctype="multipart/form-data" class="tcu-form bg-white border p-4 p-md-5" data-upcycling-form>
          @csrf
          @method('PUT')
          @include('upcycling.partials.form-etape', ['numeroParDefaut' => $etape->numero])

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-dark text-uppercase">Enregistrer l'étape</button>
            <a href="{{ route('upcycling.projets.etapes.index', $projet) }}" class="btn btn-outline-dark text-uppercase">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
