@extends('layouts.front')

@section('title', 'Modifier mon dépôt')

@section('content')
  @include('depot.partials.styles')

  <section class="bg-light py-5">
    <div class="container py-md-4" style="max-width: 900px;">
      <h1 class="section-title text-center mb-5">Modifier « {{ $vetement->titre }} »</h1>

      <div class="bg-white p-4 p-md-5">
        @include('depot.partials.flash')

        <form method="POST" action="{{ route('depot.mes-depots.update', $vetement) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          @include('depot.mes-depots._form', ['submitLabel' => 'Enregistrer'])
        </form>
      </div>
    </div>
  </section>
@endsection
