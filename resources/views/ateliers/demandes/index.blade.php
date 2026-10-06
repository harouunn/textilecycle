@extends('layouts.front')

@section('title', 'Mes demandes de réparation')

@section('content')
  @include('ateliers.partials.styles')

  <section class="bg-light py-5">
    <div class="container text-center py-md-4">
      <h1 class="section-title">Mes demandes de réparation</h1>
      <p class="mb-4">Suivez le diagnostic et l'avancement de vos réparations.</p>
      <a href="{{ route('ateliers.index') }}" class="btn btn-dark text-uppercase">Choisir un atelier</a>
    </div>
  </section>

  <section class="py-5">
    <div class="container" style="max-width: 1000px;">
      @include('ateliers.partials.flash')

      @forelse ($demandes as $demande)
        <article class="border p-4 mb-4">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
              <h2 class="fs-5 text-uppercase mb-1">{{ $demande->titre }}</h2>
              <div class="small">
                <a href="{{ route('ateliers.show', $demande->atelier) }}" class="item-anchor">{{ $demande->atelier->nom }}</a>
                · {{ $demande->type_vetement->label() }} · envoyée le {{ $demande->created_at->format('d/m/Y') }}
              </div>
            </div>
            <span class="badge text-bg-{{ $demande->statut->color() }}">{{ $demande->statut->label() }}</span>
          </div>

          <div class="row g-4">
            <div @class(['col-md-8' => $demande->photo, 'col-12' => ! $demande->photo])>
              @if ($demande->description)
                <p style="white-space: pre-line;">{{ $demande->description }}</p>
              @endif
              @include('ateliers.partials.diagnostic', ['demande' => $demande])
            </div>
            @if ($demande->photo)
              <div class="col-md-4">
                <img src="{{ $demande->photo_url }}" alt="Photo de {{ $demande->titre }}" class="img-fluid">
              </div>
            @endif
          </div>

          @can('delete', $demande)
            <form method="POST" action="{{ route('ateliers.demandes.destroy', $demande) }}" class="mt-3 text-end" onsubmit="return confirm(@js("Annuler la demande « {$demande->titre} » ?"))">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger">Annuler la demande</button>
            </form>
          @endcan
        </article>
      @empty
        <p class="text-center py-5">Vous n'avez encore fait aucune demande de réparation.</p>
      @endforelse

      <div class="mt-4">
        {{ $demandes->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </section>
@endsection
