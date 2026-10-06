@extends('layouts.front')

@section('title', 'Déposer un vêtement')

@section('content')
  @include('depot.partials.styles')

  <section class="bg-light py-5">
    <div class="container py-md-4" style="max-width: 900px;">
      <h1 class="section-title text-center mb-2">Déposer un vêtement</h1>
      <p class="text-center mb-5">Décrivez votre vêtement : nous lui trouverons la meilleure seconde vie.</p>

      <div class="bg-white p-4 p-md-5">
        @include('depot.partials.flash')
        @include('depot.partials.analyse')

        <form method="POST" action="{{ route('depot.mes-depots.store') }}" enctype="multipart/form-data">
          @csrf
          @include('depot.mes-depots._form', ['submitLabel' => 'Déposer mon vêtement', 'analyseRoute' => 'depot.mes-depots.analyser'])
        </form>
      </div>
    </div>
  </section>
@endsection
