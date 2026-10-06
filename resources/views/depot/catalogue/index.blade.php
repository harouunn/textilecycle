@extends('layouts.front')

@section('title', 'Catalogue')

@section('content')
  @include('depot.partials.styles')

  <section class="bg-light py-5">
    <div class="container text-center py-md-4">
      <h1 class="section-title">Catalogue</h1>
      <p class="mb-4">Des vêtements déposés par notre communauté, prêts pour une seconde vie.</p>
      @auth
        <a href="{{ route('depot.mes-depots.create') }}" class="btn btn-dark text-uppercase">Déposer un vêtement</a>
      @else
        <a href="{{ route('login') }}" class="btn btn-outline-dark text-uppercase">Connectez-vous pour déposer un vêtement</a>
      @endauth
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="row g-5">
        {{-- Filtre par catégorie --}}
        <aside class="col-lg-3">
          <h5 class="widget-title text-uppercase mb-3">Catégories</h5>
          <ul class="list-unstyled tc-filter-list border-top pt-2">
            <li>
              <a href="{{ route('depot.catalogue.index') }}" @class(['active' => ! $categorieActive])>
                <span>Toutes les catégories</span>
                <span>{{ $categories->sum('vetements_count') }}</span>
              </a>
            </li>
            @foreach ($categories as $categorie)
              <li>
                <a href="{{ route('depot.catalogue.index', ['categorie' => $categorie->id]) }}" @class(['active' => $categorieActive === $categorie->id])>
                  <span>{{ $categorie->nom }}</span>
                  <span>{{ $categorie->vetements_count }}</span>
                </a>
              </li>
            @endforeach
          </ul>
        </aside>

        {{-- Vêtements --}}
        <div class="col-lg-9">
          <p class="mb-4">{{ $vetements->total() }} vêtement(s) disponible(s)</p>

          <div class="row g-4">
            @forelse ($vetements as $vetement)
              <div class="col-6 col-md-4">
                @include('depot.partials.vetement-card', ['vetement' => $vetement])
              </div>
            @empty
              <div class="col-12">
                <p class="text-center py-5">Aucun vêtement disponible dans cette catégorie pour le moment.</p>
              </div>
            @endforelse
          </div>

          <div class="mt-5">
            {{ $vetements->links('pagination::bootstrap-5') }}
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
