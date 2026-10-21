@extends('layouts.front')

@section('title', 'Campagnes de collecte')

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Associations & dons" titre="Campagnes de collecte"
    sous-titre="Les associations partenaires lancent des collectes ciblées : chaque kilo reçu fait avancer la jauge."
    :fil="['Dons' => route('dons.index'), 'Campagnes' => null]">
    <a href="{{ route('dons.index') }}" class="tc-btn">Toutes les associations</a>
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    <div class="row g-4">
      @forelse ($campagnes as $campagne)
        <div class="col-md-6 col-lg-4">
          @include('dons.partials.campagne-carte', ['campagne' => $campagne])
        </div>
      @empty
        <div class="col-12">
          <p class="text-center text-muted py-5 mb-0">Aucune campagne de collecte en cours pour le moment.</p>
        </div>
      @endforelse
    </div>

    <div class="d-flex justify-content-center mt-5">
      {{ $campagnes->links('dons.partials.pagination') }}
    </div>
  </div>
</section>
@include('dons.partials.assistant')
@endsection
