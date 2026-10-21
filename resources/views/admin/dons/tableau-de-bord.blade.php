@extends('layouts.back')

@section('title', 'Tableau de bord — Dons')

@include('admin.dons.partials.styles')

@section('content')
@php
  $fmt = fn ($n, $d = 0) => number_format((float) $n, $d, ',', ' ');
  $cartes = [
    ['Textiles reçus', $fmt($kpis['total_kg'], 1).' kg', 'bx-package', 'success', 'dons reçus ou distribués'],
    ['Dons ce mois', $fmt($kpis['dons_ce_mois']), 'bx-calendar', 'primary', 'proposés depuis le 1er '.now()->locale('fr')->translatedFormat('F')],
    ['Taux d\'acceptation', $kpis['taux_acceptation'] === null ? '—' : $fmt($kpis['taux_acceptation'], 1).' %', 'bx-check-circle', 'info', 'acceptés / (acceptés + refusés)'],
    ['Associations actives', $fmt($kpis['associations_actives']), 'bx-buildings', 'warning', 'visibles sur le site'],
  ];
@endphp

<div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
  <div>
    <h4 class="text-h4 mb-1">Associations &amp; dons</h4>
    <p class="mb-0 text-medium-emphasis">Indicateurs mis à jour toutes les 10 minutes.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.index'), 'label' => 'Dons', 'icon' => 'bx-donate-heart'])
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.campagnes.index'), 'label' => 'Campagnes', 'variant' => 'tonal', 'icon' => 'bx-target-lock'])
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.associations.index'), 'label' => 'Associations', 'variant' => 'tonal', 'icon' => 'bx-buildings'])
  </div>
</div>

<div class="v-row mb-2">
  @foreach ($cartes as [$libelle, $valeur, $icone, $couleur, $aide])
    <div class="v-col-sm-6 v-col-lg-3 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
        <div class="v-card-text d-flex align-center gap-4">
          <span class="tcd-logo text-{{ $couleur }}" style="width: 46px; height: 46px; font-size: 1.5rem"><i class="{{ $icone }} v-icon notranslate v-theme--light" aria-hidden="true"></i></span>
          <div>
            <p class="mb-0 text-medium-emphasis">{{ $libelle }}</p>
            <h5 class="text-h5 mb-0">{{ $valeur }}</h5>
            <span class="text-body-2 text-medium-emphasis">{{ $aide }}</span>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  @endforeach
</div>

<div class="v-row">
  <div class="v-col-lg-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Kilos reçus par mois (12 mois)</div></div></div>
      <div class="v-card-text"><div style="height: 300px"><canvas id="graphique-kg-mois" aria-label="Kilos reçus par mois" role="img"></canvas></div></div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-lg-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Dons par type d'article</div></div></div>
      <div class="v-card-text"><div style="height: 300px"><canvas id="graphique-types" aria-label="Répartition des dons par type d'article" role="img"></canvas></div></div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-lg-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Top 5 des associations (kg reçus)</div></div></div>
      <div class="v-card-text">
        @if (empty($topAssociations['data']))
          <p class="text-medium-emphasis mb-0">Aucun don reçu pour le moment.</p>
        @else
          <div style="height: 260px"><canvas id="graphique-associations" aria-label="Top 5 des associations par kilos reçus" role="img"></canvas></div>
        @endif
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-lg-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Impact environnemental</div></div></div>
      <div class="v-card-text">
        <dl class="tcd-dl">
          <dt>CO₂ évité</dt><dd>{{ $fmt($impact['co2_kg']) }} kg</dd>
          <dt>Eau économisée</dt><dd>{{ $fmt($impact['eau_litres']) }} L</dd>
          <dt>Dons reçus</dt><dd>{{ $fmt($impact['dons']) }}</dd>
        </dl>
        <p class="tcd-hint mt-3 mb-0">Coefficients : config/textilecycle.php (dons.impact).</p>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_v('vendor/dons/chart.umd.min.js') }}"></script>
<script>
  (function () {
    const couleurs = ['#696cff', '#71dd37', '#03c3ec', '#ffab00', '#ff3e1d', '#8592a3'];
    const kgParMois = @js($kgParMois);
    const parType = @js($parType);
    const top = @js($topAssociations);

    new Chart(document.getElementById('graphique-kg-mois'), {
      type: 'bar',
      data: { labels: kgParMois.labels, datasets: [{ label: 'kg reçus', data: kgParMois.data, backgroundColor: couleurs[0], borderRadius: 4 }] },
      options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'kg' } } } },
    });

    new Chart(document.getElementById('graphique-types'), {
      type: 'doughnut',
      data: { labels: parType.labels, datasets: [{ data: parType.data, backgroundColor: couleurs }] },
      options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });

    const canvasTop = document.getElementById('graphique-associations');
    if (canvasTop) {
      new Chart(canvasTop, {
        type: 'bar',
        data: { labels: top.labels, datasets: [{ label: 'kg reçus', data: top.data, backgroundColor: couleurs[1], borderRadius: 4 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } },
      });
    }
  })();
</script>
@endpush
