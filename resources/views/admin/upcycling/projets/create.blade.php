@extends('layouts.back')

@section('title', 'Nouveau projet d\'upcycling')

@section('content')
@include('admin.upcycling.partials.styles')
<div class="mb-6">
  <a href="{{ route('admin.upcycling.projets.index') }}" class="text-body-2"><i class="bx-arrow-back v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Retour aux projets</a>
  <h4 class="text-h4 mt-2 mb-1">Nouveau projet d'upcycling</h4>
  <p class="mb-0 text-body-1">Les étapes se gèrent ensuite depuis la page du projet.</p>
</div>

@include('admin.upcycling.partials.flash')

<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
  <div class="v-card-text">
    <form method="POST" action="{{ route('admin.upcycling.projets.store') }}" enctype="multipart/form-data" data-upcycling-form>
      @csrf
      @include('admin.upcycling.projets._form')

      <div class="d-flex gap-4 mt-6">
        <button type="submit" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated">
          <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
          <span class="v-btn__content">Créer le projet</span>
        </button>
        <a href="{{ route('admin.upcycling.projets.index') }}" class="v-btn v-theme--light text-secondary v-btn--density-default v-btn--size-default v-btn--variant-outlined">
          <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
          <span class="v-btn__content">Annuler</span>
        </a>
      </div>
    </form>
  </div>
  <span class="v-card__underlay"></span>
</div>
@endsection
