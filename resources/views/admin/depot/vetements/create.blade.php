@extends('layouts.back')

@section('title', 'Ajouter un vêtement')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')
  @include('admin.depot.partials.analyse')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">Ajouter un vêtement</div>
      </div>
    </div>
    <div class="v-card-text">
      <form method="POST" action="{{ route('admin.depot.vetements.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.depot.vetements._form')
      </form>
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
