@extends('layouts.back')

@section('title', 'Nouvelle catégorie')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">Nouvelle catégorie</div>
      </div>
    </div>
    <div class="v-card-text">
      <form method="POST" action="{{ route('admin.depot.categories.store') }}">
        @csrf
        @include('admin.depot.categories._form')
      </form>
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
