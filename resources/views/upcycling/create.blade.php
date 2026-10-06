@extends('layouts.front')

@section('title', 'Proposer un projet d\'upcycling')

@section('content')
@include('upcycling.partials.styles')

<section class="bg-light py-5">
  <div class="container py-md-4">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <h1 class="section-title text-center mb-2">Proposer un projet</h1>
        <p class="text-center mb-4">Partagez votre idée de transformation. Vous ajouterez les étapes juste après. Votre projet sera publié après validation par l'équipe.</p>

        @include('upcycling.partials.flash')

        <form method="POST" action="{{ route('upcycling.projets.store') }}" enctype="multipart/form-data" class="tcu-form bg-white border p-4 p-md-5" data-upcycling-form>
          @csrf
          @include('upcycling.partials.form-projet')

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-dark text-uppercase">Enregistrer et ajouter les étapes</button>
            <a href="{{ route('upcycling.mes-projets') }}" class="btn btn-outline-dark text-uppercase">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
