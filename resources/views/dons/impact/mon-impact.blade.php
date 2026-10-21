@extends('layouts.front')

@section('title', 'Mon impact')

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Mon espace" titre="Mon impact" sous-titre="Ce que vos dons reçus par les associations ont permis d'éviter."
    :fil="['Dons' => route('dons.index'), 'Mon impact' => null]">
    <a href="{{ route('dons.mes-dons') }}" class="tc-btn">Mes dons</a>
    <a href="{{ route('dons.classement') }}" class="tc-btn">Classement</a>
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="p-4 bg-dark text-white rounded h-100 text-center">
          <div class="display-6 fw-semibold">{{ number_format($points, 0, ',', ' ') }}</div>
          <div class="small text-uppercase">points</div>
          <div class="small opacity-75 mt-2">{{ config('textilecycle.dons.points_par_kg') }} points par kilo reçu · {{ $impact['dons'] }} don(s) reçu(s)</div>
        </div>
      </div>
      <div class="col-md-8">
        @include('dons.partials.impact', ['impact' => $impact])
      </div>
    </div>

    <h2 class="h4 text-uppercase mb-3">Mes badges</h2>
    <div class="row g-3">
      @foreach ($badges as $badge)
        <div class="col-sm-6 col-lg-3">
          <div @class(['card h-100', 'border-success' => $badge['obtenu'], 'border-0 bg-light' => ! $badge['obtenu']])>
            <div class="card-body text-center">
              <div class="fs-1" aria-hidden="true">{{ $badge['obtenu'] ? '🏅' : '🔒' }}</div>
              <h3 class="h6 text-uppercase mt-2 mb-1">{{ $badge['titre'] }}</h3>
              <p class="small text-muted mb-2">{{ $badge['description'] }}</p>
              @if ($badge['obtenu'])
                <span class="badge text-bg-success">Obtenu</span>
              @else
                <div class="progress" role="progressbar" aria-label="Progression vers le badge {{ $badge['titre'] }}"
                  aria-valuenow="{{ $badge['progression'] }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px">
                  <div class="progress-bar bg-dark" style="width: {{ $badge['progression'] }}%"></div>
                </div>
                <div class="small text-muted mt-1">{{ $badge['progression'] }} %</div>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endsection
