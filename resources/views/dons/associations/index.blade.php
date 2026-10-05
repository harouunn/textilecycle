@extends('layouts.front')

@section('title', 'Associations partenaires')

@section('content')
<section class="bg-light py-5">
  <div class="container">
    <div class="row justify-content-center text-center">
      <div class="col-lg-8">
        <h1 class="section-title mt-3">Donnez à une association</h1>
        <p class="mb-0">Choisissez une association partenaire, découvrez ses besoins et proposez-lui vos vêtements en bon état.</p>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <form method="GET" action="{{ route('dons.index') }}" class="d-flex flex-wrap align-items-end gap-2">
        <div>
          <label for="ville" class="form-label small text-uppercase mb-1">Filtrer par ville</label>
          <select id="ville" name="ville" class="form-select" onchange="this.form.submit()">
            <option value="">Toutes les villes</option>
            @foreach ($villes as $v)
              <option value="{{ $v }}" @selected($ville === $v)>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        <noscript><button type="submit" class="btn btn-dark">Filtrer</button></noscript>
        @if ($ville !== '')
          <a href="{{ route('dons.index') }}" class="btn btn-link">Réinitialiser</a>
        @endif
      </form>

      @auth
        <a href="{{ route('dons.mes-dons') }}" class="btn btn-outline-dark rounded-pill">Mes dons</a>
      @endauth
    </div>

    <div class="row g-4">
      @forelse ($associations as $association)
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-center gap-3 mb-3">
                @if ($association->logo_url)
                  <img src="{{ $association->logo_url }}" alt="" width="56" height="56" class="rounded object-fit-cover">
                @else
                  <span class="d-inline-flex align-items-center justify-content-center rounded bg-light fs-4" style="width:56px;height:56px">{{ mb_strtoupper(mb_substr($association->nom, 0, 1)) }}</span>
                @endif
                <div>
                  <h5 class="card-title mb-0">{{ $association->nom }}</h5>
                  <small class="text-muted">{{ $association->ville }}</small>
                </div>
              </div>
              <p class="card-text">{{ \Illuminate\Support\Str::limit($association->description, 140) }}</p>
              <p class="small mb-4"><strong>Besoins :</strong> {{ \Illuminate\Support\Str::limit(str_replace("\n", ' · ', $association->besoins), 110) }}</p>
              <div class="mt-auto d-flex gap-2">
                <a href="{{ route('dons.associations.show', $association) }}" class="btn btn-outline-dark btn-sm">Voir les besoins</a>
                <a href="{{ route('dons.create', $association) }}" class="btn btn-dark btn-sm">Faire un don</a>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <p class="text-center text-muted py-5 mb-0">Aucune association active {{ $ville !== '' ? 'à '.$ville : 'pour le moment' }}.</p>
        </div>
      @endforelse
    </div>

    <div class="d-flex justify-content-center mt-5">
      {{ $associations->links('pagination::bootstrap-5') }}
    </div>
  </div>
</section>
@endsection
