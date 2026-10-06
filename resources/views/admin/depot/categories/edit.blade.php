@extends('layouts.back')

@section('title', 'Modifier la catégorie')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">Modifier la catégorie « {{ $categorie->nom }} »</div>
      </div>
    </div>
    <div class="v-card-text">
      <form method="POST" action="{{ route('admin.depot.categories.update', $categorie) }}">
        @csrf
        @method('PUT')
        @include('admin.depot.categories._form')
      </form>
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
