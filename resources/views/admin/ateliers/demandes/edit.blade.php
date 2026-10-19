@extends('layouts.back')

@section('title', 'Modifier la demande')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">Modifier « {{ $demande->titre }} »</div>
      </div>
    </div>
    <div class="v-card-text">
      <form method="POST" action="{{ route('admin.ateliers.demandes.update', $demande) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.ateliers.demandes._form')
      </form>
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
