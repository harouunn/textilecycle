@extends('layouts.front')

@section('title', 'Ateliers & réparations')

@section('content')
  @include('ateliers.partials.styles')

  <section class="bg-light py-5">
    <div class="container text-center py-md-4">
      <h1 class="section-title">Ateliers &amp; réparations</h1>
      <p class="mb-4">Un vêtement abîmé ? Décrivez-le ou envoyez une photo : nous estimons le coût et le délai de la réparation.</p>
      @auth
        <a href="{{ route('ateliers.demandes.index') }}" class="btn btn-outline-dark text-uppercase">Mes demandes</a>
      @endauth
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="row g-5">
        {{-- Filtre par ville --}}
        <aside class="col-lg-3">
          <h5 class="widget-title text-uppercase mb-3">Villes</h5>
          <ul class="list-unstyled tc-filter-list border-top pt-2">
            <li>
              <a href="{{ route('ateliers.index') }}" @class(['active' => empty($filters['ville'])])>Toutes les villes</a>
            </li>
            @foreach ($villes as $ville)
              <li>
                <a href="{{ route('ateliers.index', ['ville' => $ville]) }}" @class(['active' => ($filters['ville'] ?? null) === $ville])>{{ $ville }}</a>
              </li>
            @endforeach
          </ul>
        </aside>

        {{-- Ateliers --}}
        <div class="col-lg-9">
          <p class="mb-4">{{ $ateliers->total() }} atelier(s) partenaire(s)</p>

          <div class="row g-4">
            @forelse ($ateliers as $atelier)
              <div class="col-md-6 col-xl-4">
                <div class="tc-atelier-card">
                  <h5 class="text-uppercase fs-6 mb-1">
                    <a href="{{ route('ateliers.show', $atelier) }}" class="item-anchor">{{ $atelier->nom }}</a>
                  </h5>
                  <div class="small mb-3">{{ $atelier->ville }} · {{ $atelier->code_postal }}</div>
                  <p class="small">{{ Str::limit($atelier->description ?? $atelier->adresse, 120) }}</p>
                  <a href="{{ route('ateliers.show', $atelier) }}" class="btn btn-sm btn-outline-dark text-uppercase align-self-start">Voir l'atelier</a>
                </div>
              </div>
            @empty
              <div class="col-12">
                <p class="text-center py-5">Aucun atelier partenaire pour le moment.</p>
              </div>
            @endforelse
          </div>

          <div class="mt-5">
            {{ $ateliers->links('pagination::bootstrap-5') }}
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
