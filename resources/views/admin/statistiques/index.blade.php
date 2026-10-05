@extends('layouts.back')

@section('title', 'Statistiques')

@include('admin.dons.partials.styles')

@push('styles')
<style>
  /* Bar charts in HTML/CSS: one hue for magnitude, labels and values in text ink, never color alone. */
  .tcs-section-title { display: flex; align-items: center; gap: .5rem; margin: 1rem 0 0; }
  .tcs-hbar { display: grid; grid-template-columns: minmax(110px, 34%) 1fr auto; align-items: center; gap: .5rem .75rem; padding: .125rem .25rem; }
  .tcs-hbar + .tcs-hbar { margin-top: .5rem; }
  .tcs-hbar-label { font-size: .875rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .tcs-track { position: relative; height: 12px; }
  .tcs-fill { position: absolute; inset-block: 0; inset-inline-start: 0; min-width: 2px; border-radius: 0 4px 4px 0; background: rgb(var(--v-theme-primary)); transition: opacity .15s; }
  .tcs-hbar:hover { background: rgba(var(--v-theme-on-surface), .04); border-radius: 4px; }
  .tcs-hbar:hover .tcs-fill { opacity: .8; }
  .tcs-value { font-size: .875rem; font-variant-numeric: tabular-nums; color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); white-space: nowrap; }
  .tcs-value small { color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); }

  .tcs-stack { display: flex; gap: 2px; height: 20px; margin-bottom: 1rem; }
  .tcs-stack > span { min-width: 2px; }
  .tcs-stack > span:first-child { border-radius: 4px 0 0 4px; }
  .tcs-stack > span:last-child { border-radius: 0 4px 4px 0; }
  .tcs-legend { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: .75rem; }
  .tcs-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-inline-end: .375rem; }

  .tcs-num { font-variant-numeric: tabular-nums; }
  .tcs-placeholder { border: 1px dashed rgba(var(--v-border-color), .35); border-radius: 6px; padding: 1.25rem; text-align: center; }
</style>
@endpush

@section('content')
@php
  $fmtKg = fn ($kg) => number_format((float) $kg, 2, ',', ' ').' kg';
  $tiles = [
    ['Utilisateurs inscrits', $vueEnsemble['users'], 'bx-user', 'primary', $vueEnsemble['actifs'].' actif(s) (dépôt ou don)', route('admin.users.index')],
    ['Vêtements déposés', $vueEnsemble['vetements'] ?? '—', 'bx-package', 'info', $modules['depot']['installe'] ? 'Module Dépôt' : 'Module Dépôt non installé', null],
    ['Dons aux associations', $vueEnsemble['dons'], 'bx-donate-heart', 'warning', $fmtKg($dons['totals']['poids']).' proposés', route('admin.dons.dons.index')],
    ['Textiles redistribués', $fmtKg($vueEnsemble['kg_redistribues']), 'bx-leaf', 'success', 'Dons reçus par les associations', route('admin.dons.dons.index', ['statut' => 'recu'])],
  ];
  $installes = collect($modules)->where('installe', true)->count();
@endphp

<div class="mb-6">
  <h4 class="text-h4 mb-1">Statistiques</h4>
  <p class="mb-0 text-medium-emphasis">Vue d'ensemble de TexTileCycle : {{ $installes }} module(s) sur {{ count($modules) }} installé(s).</p>
</div>

<div class="v-row">
  {{-- ===================== Vue d'ensemble ===================== --}}
  @foreach ($tiles as [$tileLabel, $tileValue, $tileIcon, $tileColor, $tileHint, $tileHref])
    <div class="v-col-sm-6 v-col-lg-3 v-col-12">
      <{!! $tileHref ? 'a href="'.e($tileHref).'"' : 'div' !!} class="v-card v-theme--light v-card--density-default v-card--variant-elevated d-block h-100" style="text-decoration: none;">
        <div class="v-card-text d-flex align-center gap-4">
          <div class="v-avatar v-theme--light text-{{ $tileColor }} v-avatar--density-default v-avatar--variant-tonal rounded flex-shrink-0" style="width: 42px; height: 42px;">
            <i class="{{ $tileIcon }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 24px; height: 24px; width: 24px;"></i>
            <span class="v-avatar__underlay"></span>
          </div>
          <div>
            <p class="mb-0 text-medium-emphasis">{{ $tileLabel }}</p>
            <h5 class="text-h5 mb-0 tcs-num">{{ $tileValue }}</h5>
            <p class="mb-0 text-body-2 text-medium-emphasis">{{ $tileHint }}</p>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </{!! $tileHref ? 'a' : 'div' !!}>
    </div>
  @endforeach

  {{-- Activité mensuelle --}}
  <div class="v-col-md-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Activité des 6 derniers mois</div>
          <div class="v-card-subtitle">Nouveaux éléments créés chaque mois, tous modules confondus</div>
        </div>
      </div>
      <div class="v-table v-theme--light v-table--density-compact tcd-table">
        <div class="v-table__wrapper">
          <table>
            <thead>
              <tr>
                <th>Mois</th>
                <th class="text-end">Inscriptions</th>
                <th class="text-end">Vêtements déposés</th>
                <th class="text-end">Dons proposés</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($activite as $row)
                <tr>
                  <td>{{ $row['label'] }}</td>
                  <td class="text-end tcs-num">{{ $row['inscriptions'] }}</td>
                  <td class="text-end tcs-num">{{ $row['depots'] ?? '—' }}</td>
                  <td class="text-end tcs-num">{{ $row['dons'] }}</td>
                </tr>
              @endforeach
              <tr class="font-weight-medium">
                <td>Total</td>
                <td class="text-end tcs-num">{{ $activite->sum('inscriptions') }}</td>
                <td class="text-end tcs-num">{{ $modules['depot']['installe'] ? $activite->sum('depots') : '—' }}</td>
                <td class="text-end tcs-num">{{ $activite->sum('dons') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  {{-- État des modules --}}
  <div class="v-col-md-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Modules</div></div></div>
      <div class="v-card-text">
        @foreach ($modules as $module)
          <div class="d-flex align-center justify-space-between gap-3 {{ $loop->last ? '' : 'mb-4' }}">
            <div class="d-flex align-center gap-3">
              <i class="{{ $module['icone'] }} v-icon notranslate v-theme--light" aria-hidden="true"></i>
              <span>{{ $module['titre'] }}</span>
            </div>
            <span class="v-chip v-theme--light text-{{ $module['installe'] ? 'success' : 'secondary' }} v-chip--density-default v-chip--size-small v-chip--variant-tonal">
              <span class="v-chip__underlay"></span>
              <div class="v-chip__content">{{ $module['installe'] ? 'Installé' : 'Pas encore installé' }}</div>
            </span>
          </div>
        @endforeach
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  {{-- ===================== Dépôt & vêtements ===================== --}}
  <div class="v-col-12">
    <h5 class="text-h5 tcs-section-title"><i class="bx-package v-icon notranslate v-theme--light" aria-hidden="true"></i> Dépôt &amp; vêtements</h5>
  </div>
  @if ($depot)
    <div class="v-col-md-4 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">En chiffres</div></div></div>
        <div class="v-card-text">
          <dl class="tcd-dl">
            <dt>Vêtements déposés</dt><dd class="tcs-num">{{ $depot['total'] }}</dd>
            <dt>Déposants</dt><dd class="tcs-num">{{ $depot['deposants'] }}</dd>
            <dt>Catégories</dt><dd class="tcs-num">{{ $depot['categories'] }}</dd>
          </dl>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
    <div class="v-col-md-8 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
        <div class="v-card-item">
          <div class="v-card-item__content">
            <div class="v-card-title">Vêtements par statut</div>
            <div class="v-card-subtitle">Répartition des {{ $depot['total'] }} vêtements déposés</div>
          </div>
        </div>
        <div class="v-card-text">
          @include('admin.statistiques.partials.stack', ['stackRows' => $depot['parStatut'], 'stackAria' => 'Répartition des vêtements par statut'])
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
    <div class="v-col-md-6 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Vêtements par catégorie</div></div></div>
        <div class="v-card-text">
          @include('admin.statistiques.partials.hbars', [
            'barRows' => $depot['parCategorie']->map(fn ($c) => ['label' => $c->nom, 'value' => $c->total, 'text' => $c->total.' vêtement(s)']),
            'barEmpty' => 'Aucune catégorie pour le moment.',
          ])
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
    <div class="v-col-md-6 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Vêtements par état</div></div></div>
        <div class="v-card-text">
          @include('admin.statistiques.partials.hbars', [
            'barRows' => $depot['parEtat']->map(fn ($e) => ['label' => $e['label'], 'value' => $e['total'], 'text' => (string) $e['total']]),
          ])
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  @else
    <div class="v-col-12">
      <div class="tcs-placeholder text-medium-emphasis">
        Le module Dépôt n'est pas encore installé dans ce projet : ses statistiques (vêtements par statut, catégorie et état)
        apparaîtront automatiquement dès que ses tables <code>vetements</code> et <code>categories</code> existeront.
      </div>
    </div>
  @endif

  {{-- ===================== Ateliers & Upcycling ===================== --}}
  @foreach (['ateliers', 'upcycling'] as $cle)
    <div class="v-col-md-6 v-col-12">
      <h5 class="text-h5 tcs-section-title mb-3"><i class="{{ $modules[$cle]['icone'] }} v-icon notranslate v-theme--light" aria-hidden="true"></i> {{ $modules[$cle]['titre'] }}</h5>
      <div class="tcs-placeholder text-medium-emphasis">
        Module pas encore installé : ses statistiques seront ajoutées ici quand l'équipe l'aura livré.
      </div>
    </div>
  @endforeach

  {{-- ===================== Associations & dons ===================== --}}
  <div class="v-col-12">
    <h5 class="text-h5 tcs-section-title"><i class="bx-donate-heart v-icon notranslate v-theme--light" aria-hidden="true"></i> Associations &amp; dons</h5>
  </div>
  <div class="v-col-md-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">En chiffres</div></div></div>
      <div class="v-card-text">
        <dl class="tcd-dl">
          <dt>Associations</dt><dd class="tcs-num">{{ $dons['totals']['associations'] }} <span class="text-medium-emphasis">({{ $dons['totals']['associations_actives'] }} active(s))</span></dd>
          <dt>Dons proposés</dt><dd class="tcs-num">{{ $dons['totals']['dons'] }}</dd>
          <dt>Poids proposé</dt><dd class="tcs-num">{{ $fmtKg($dons['totals']['poids']) }}</dd>
          <dt>Poids reçu</dt><dd class="tcs-num">{{ $fmtKg($dons['totals']['poids_recu']) }}</dd>
          <dt>Articles reçus</dt><dd class="tcs-num">{{ $dons['totals']['articles_recus'] }}</dd>
        </dl>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-md-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Dons par statut</div>
          <div class="v-card-subtitle">Répartition des {{ $dons['totals']['dons'] }} dons</div>
        </div>
      </div>
      <div class="v-card-text">
        @include('admin.statistiques.partials.stack', [
          'stackRows' => $dons['parStatut']->map(fn ($s, $statut) => [...$s, 'href' => route('admin.dons.dons.index', ['statut' => $statut])]),
          'stackAria' => 'Répartition des dons par statut',
        ])
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-md-6 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Articles donnés par type</div>
          <div class="v-card-subtitle">Nombre d'articles (et nombre de dons)</div>
        </div>
      </div>
      <div class="v-card-text">
        @include('admin.statistiques.partials.hbars', [
          'barRows' => $dons['parType']->map(fn ($t, $type) => [
            'label' => $t['label'], 'value' => $t['articles'], 'text' => (string) $t['articles'], 'hint' => $t['total'].' don(s)',
            'href' => route('admin.dons.dons.index', ['type_article' => $type]),
          ]),
        ])
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-md-6 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Poids donné par association</div>
          <div class="v-card-subtitle">Tous statuts confondus</div>
        </div>
      </div>
      <div class="v-card-text">
        @include('admin.statistiques.partials.hbars', [
          'barRows' => $dons['parAssociation']->map(fn ($a) => [
            'label' => $a->nom, 'value' => (float) $a->dons_sum_poids_kg, 'text' => $fmtKg($a->dons_sum_poids_kg), 'hint' => $a->dons_count.' don(s)',
            'href' => route('admin.dons.associations.show', $a),
          ]),
          'barEmpty' => 'Aucune association pour le moment.',
        ])
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Meilleurs donateurs</div>
          <div class="v-card-subtitle">Classés par nombre de dons proposés</div>
        </div>
      </div>
      <div class="v-table v-theme--light v-table--density-default tcd-table">
        <div class="v-table__wrapper">
          <table>
            <thead>
              <tr><th>#</th><th>Utilisateur</th><th class="text-center">Dons</th><th class="text-center">Articles</th></tr>
            </thead>
            <tbody>
              @forelse ($dons['topDonateurs'] as $donateur)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>
                    <a href="{{ route('admin.users.show', $donateur) }}" class="font-weight-medium">{{ $donateur->name }}</a>
                    <div class="text-body-2 text-medium-emphasis">{{ $donateur->email }}</div>
                  </td>
                  <td class="text-center tcs-num">{{ $donateur->dons_count }}</td>
                  <td class="text-center tcs-num">{{ $donateur->dons_sum_quantite }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-medium-emphasis py-6">Aucun don pour le moment.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
