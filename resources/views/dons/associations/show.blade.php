@extends('layouts.front')

@section('title', $association->nom)

@section('content')
<section class="bg-light py-5">
  <div class="container">
    <a href="{{ route('dons.index') }}" class="small text-uppercase">‹ Toutes les associations</a>
    <div class="d-flex flex-wrap align-items-center gap-4 mt-3">
      @if ($association->logo_url)
        <img src="{{ $association->logo_url }}" alt="Logo {{ $association->nom }}" width="96" height="96" class="rounded object-fit-cover">
      @endif
      <div>
        <h1 class="section-title mb-1">{{ $association->nom }}</h1>
        <p class="mb-0 text-muted">{{ $association->ville }} · {{ $association->dons_recus_count }} don(s) déjà reçu(s) via TexTileCycle</p>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    <div class="row g-5">
      <div class="col-lg-8">
        <h4 class="text-uppercase mb-3">Qui sommes-nous ?</h4>
        <p style="white-space: pre-line">{{ $association->description }}</p>

        <h4 class="text-uppercase mt-5 mb-3">Nos besoins</h4>
        <ul class="list-group list-group-flush mb-4">
          @foreach (preg_split('/\r\n|\r|\n/', trim($association->besoins)) as $besoin)
            @if (trim($besoin) !== '')
              <li class="list-group-item px-0">✓ {{ trim($besoin) }}</li>
            @endif
          @endforeach
        </ul>

        <a href="{{ route('dons.create', $association) }}" class="btn btn-dark btn-lg rounded-pill px-5">Faire un don</a>
        @guest
          <p class="small text-muted mt-2 mb-0">Vous devrez vous <a href="{{ route('login') }}">connecter</a> pour proposer un don.</p>
        @endguest
      </div>

      <div class="col-lg-4">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h5 class="text-uppercase mb-3">Contact</h5>
            <p class="mb-2">{{ $association->adresse }}<br>{{ $association->ville }}</p>
            <p class="mb-2"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $association->telephone) }}">{{ $association->telephone }}</a></p>
            <p class="mb-2"><a href="mailto:{{ $association->email }}">{{ $association->email }}</a></p>
            @if ($association->site_web)
              <p class="mb-0"><a href="{{ $association->site_web }}" target="_blank" rel="noopener">Site web ↗</a></p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
