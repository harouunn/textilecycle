@extends('layouts.back')

@section('title', 'Modifier le vêtement')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">Modifier « {{ $vetement->titre }} »</div>
      </div>
    </div>
    <div class="v-card-text">
      <form method="POST" action="{{ route('admin.depot.vetements.update', $vetement) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.depot.vetements._form')
      </form>
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
