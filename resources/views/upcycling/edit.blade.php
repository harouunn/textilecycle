@extends('layouts.front')

@section('title', 'Modifier mon projet')

@section('content')
@include('upcycling.partials.styles')

<section class="bg-light py-5">
  <div class="container py-md-4">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <a href="{{ route('upcycling.mes-projets') }}" class="item-anchor small text-uppercase">← Mes projets</a>
        <h1 class="section-title text-center mt-3 mb-2">Modifier mon projet</h1>
        <p class="text-center mb-4">
          @include('upcycling.partials.badge', ['enum' => $projet->statut])
          <a href="{{ route('upcycling.projets.etapes.index', $projet) }}" class="item-anchor ms-2">Gérer les étapes →</a>
        </p>

        @include('upcycling.partials.flash')

        <form method="POST" action="{{ route('upcycling.projets.update', $projet) }}" enctype="multipart/form-data" class="tcu-form bg-white border p-4 p-md-5" data-upcycling-form>
          @csrf
          @method('PUT')
          @include('upcycling.partials.form-projet')

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-dark text-uppercase">Enregistrer les modifications</button>
            <a href="{{ route('upcycling.mes-projets') }}" class="btn btn-outline-dark text-uppercase">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
