@extends('layouts.back')

@section('title', 'Statistiques du dépôt')

@section('content')
  @include('admin.depot.partials.styles')

  @php
    $ind = $rapport['indicateurs'];
    $taux = fn (?int $valeur) => $valeur === null ? '—' : $valeur.' %';
    $nombre = fn (int $valeur) => number_format($valeur, 0, ',', ' ');
    $maxMois = max(1, collect($rapport['par_mois'])->max(fn ($m) => max($m['depots'], $m['reservations'])));
    $statutsVetements = [
        ['En ligne', $rapport['vetements']['en_ligne'], '#3d7a4f'],
        ['À valider', $rapport['vetements']['a_valider'], '#e8a33d'],
        ['Réservés', $rapport['vetements']['reserves'], '#4a7fb5'],
        ['Donnés', $rapport['vetements']['donnes'], '#8a6fb0'],
    ];
    $statutsReservations = [
        ['En attente', $rapport['reservations']['en_attente'], '#e8a33d'],
        ['Confirmées', $rapport['reservations']['confirmee'], '#4a7fb5'],
        ['Retirées', $rapport['reservations']['retiree'], '#3d7a4f'],
        ['Annulées', $rapport['reservations']['annulee'], '#a8aca9'],
    ];
  @endphp

  <style>
    .tds-carte { height: 100%; }
    .tds-sous { font-size: .8125rem; color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); }
    .tds-num { font-variant-numeric: tabular-nums; }
    /* Histogramme vertical : dépôts et réservations par mois */
    .tds-histo { display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem; align-items: end; height: 220px; padding-top: 1.5rem; }
    .tds-histo__mois { display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; gap: .4rem; }
    .tds-histo__barres { display: flex; align-items: flex-end; gap: 4px; height: 100%; width: 100%; justify-content: center; }
    .tds-histo__barre { width: 38%; max-width: 26px; border-radius: 6px 6px 2px 2px; position: relative; min-height: 2px; }
    .tds-histo__barre span { position: absolute; top: -1.3rem; left: 50%; transform: translateX(-50%); font-size: .75rem; font-variant-numeric: tabular-nums; }
    .tds-histo__libelle { font-size: .75rem; color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); white-space: nowrap; }
    .tds-legende { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .8125rem; }
    .tds-pastille { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-inline-end: .35rem; vertical-align: middle; }
    /* Barres horizontales */
    .tds-ligne { display: grid; grid-template-columns: minmax(100px, 32%) 1fr 3.2rem; gap: .75rem; align-items: center; font-size: .875rem; }
    .tds-ligne + .tds-ligne { margin-top: .6rem; }
    .tds-piste { height: 10px; border-radius: 999px; background: rgba(var(--v-theme-on-surface), .06); overflow: hidden; }
    .tds-remplissage { height: 100%; border-radius: 999px; background: rgb(var(--v-theme-primary)); }
    .tds-ligne__valeur { text-align: end; font-variant-numeric: tabular-nums; }
    /* Barre empilée */
    .tds-pile { display: flex; height: 14px; border-radius: 999px; overflow: hidden; gap: 2px; background: rgba(var(--v-theme-on-surface), .06); margin-bottom: 1rem; }
    /* Jauge de taux */
    .tds-jauge { display: flex; align-items: center; gap: 1rem; }
    .tds-jauge__cercle { --p: 0; width: 84px; height: 84px; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center;
      background: conic-gradient(rgb(var(--v-theme-primary)) calc(var(--p) * 1%), rgba(var(--v-theme-on-surface), .08) 0); }
    .tds-jauge__cercle::before { content: attr(data-valeur); width: 64px; height: 64px; border-radius: 50%; display: grid; place-items: center;
      background: rgb(var(--v-theme-surface)); font-weight: 600; font-variant-numeric: tabular-nums; }
    .tds-impact { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center; }
    .tds-impact strong { display: block; font-size: 1.6rem; font-variant-numeric: tabular-nums; color: rgb(var(--v-theme-success)); }
  </style>

  <div class="tc-page-head">
    <div>
      <h4 class="text-h4">Statistiques du dépôt</h4>
      <p>Indicateurs du module Dépôt &amp; catalogue : dépôts, validation, réservations et seconde vie des vêtements.</p>
    </div>
    <div class="d-flex flex-wrap gap-3">
      @include('admin.depot.partials.btn', ['label' => 'Exporter (Excel)', 'icon' => 'bx-download', 'variant' => 'tonal', 'color' => 'secondary', 'href' => route('admin.depot.export.vetements')])
      @include('admin.depot.partials.btn', ['label' => 'Rapport PDF', 'icon' => 'bx-file', 'href' => route('admin.depot.statistiques.pdf')])
    </div>
  </div>

  @include('admin.depot.partials.kpis', ['kpis' => [
      ['Vêtements déposés', $nombre($ind['total']), 'bx-package', 'primary', route('admin.depot.vetements.index'), false],
      ['Déposés ce mois-ci', $nombre($ind['ce_mois']), 'bx-trending-up', 'info', null, false],
      ['À valider', $nombre($ind['a_valider']), 'bx-check-shield', 'warning', route('admin.depot.vetements.index', ['moderation' => 'en_attente']), false],
      ['En ligne au catalogue', $nombre($ind['en_ligne']), 'bx-store', 'success', route('depot.catalogue.index'), false],
      ['Réservations', $nombre($rapport['reservations']['total']), 'bx-calendar-check', 'secondary', route('admin.depot.reservations.index'), false],
  ]])

  <div class="v-row">
    {{-- Activité mensuelle --}}
    <div class="v-col-lg-8 v-col-12">
      <div class="v-card v-theme--light v-card--variant-elevated tds-carte">
        <div class="v-card-item"><div class="v-card-item__content">
          <div class="v-card-title">Activité des 6 derniers mois</div>
          <div class="v-card-subtitle">Nouveaux dépôts et nouvelles réservations par mois</div>
        </div></div>
        <div class="v-card-text">
          <div class="tds-legende">
            <span><span class="tds-pastille" style="background:#3d7a4f"></span>Dépôts</span>
            <span><span class="tds-pastille" style="background:#c8d96f"></span>Réservations</span>
          </div>
          <div class="tds-histo" role="img" aria-label="Dépôts et réservations par mois">
            @foreach ($rapport['par_mois'] as $mois)
              <div class="tds-histo__mois">
                <div class="tds-histo__barres">
                  <div class="tds-histo__barre" style="height: {{ 100 * $mois['depots'] / $maxMois }}%; background:#3d7a4f"><span>{{ $mois['depots'] }}</span></div>
                  <div class="tds-histo__barre" style="height: {{ 100 * $mois['reservations'] / $maxMois }}%; background:#c8d96f"><span>{{ $mois['reservations'] }}</span></div>
                </div>
                <div class="tds-histo__libelle">{{ $mois['mois'] }}</div>
              </div>
            @endforeach
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    {{-- Taux clés --}}
    <div class="v-col-lg-4 v-col-12">
      <div class="v-card v-theme--light v-card--variant-elevated tds-carte">
        <div class="v-card-item"><div class="v-card-item__content">
          <div class="v-card-title">Taux clés</div>
          <div class="v-card-subtitle">Qualité des dépôts et des réservations</div>
        </div></div>
        <div class="v-card-text d-flex flex-column gap-5">
          @foreach ([
              ['Taux d\'acceptation', $ind['taux_acceptation'], 'Dépôts approuvés parmi ceux déjà traités'],
              ['Taux de réussite', $ind['taux_reussite'], 'Réservations retirées parmi celles terminées'],
              ['Taux d\'expiration', $ind['taux_expiration'], $ind['expirees'].' annulation(s) automatique(s) sur '.$rapport['reservations']['annulee']],
          ] as [$libelle, $valeur, $aide])
            <div class="tds-jauge">
              <div class="tds-jauge__cercle" style="--p: {{ $valeur ?? 0 }}" data-valeur="{{ $taux($valeur) }}" role="img" aria-label="{{ $libelle }} : {{ $taux($valeur) }}"></div>
              <div><strong>{{ $libelle }}</strong><div class="tds-sous">{{ $aide }}</div></div>
            </div>
          @endforeach
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    {{-- Statuts --}}
    @foreach ([
        ['Vêtements par statut', $rapport['vetements']['total'].' vêtement(s) déposé(s)', $statutsVetements],
        ['Réservations par statut', $rapport['reservations']['total'].' réservation(s)', $statutsReservations],
    ] as [$titre, $sousTitre, $parts])
      @php $somme = max(1, collect($parts)->sum(1)); @endphp
      <div class="v-col-md-6 v-col-12">
        <div class="v-card v-theme--light v-card--variant-elevated tds-carte">
          <div class="v-card-item"><div class="v-card-item__content">
            <div class="v-card-title">{{ $titre }}</div>
            <div class="v-card-subtitle">{{ $sousTitre }}</div>
          </div></div>
          <div class="v-card-text">
            <div class="tds-pile" role="img" aria-label="{{ $titre }}">
              @foreach ($parts as [$libelle, $valeur, $couleur])
                @if ($valeur > 0)<span style="width: {{ 100 * $valeur / $somme }}%; background: {{ $couleur }}"></span>@endif
              @endforeach
            </div>
            <div class="tds-legende">
              @foreach ($parts as [$libelle, $valeur, $couleur])
                <span><span class="tds-pastille" style="background: {{ $couleur }}"></span>{{ $libelle }} <strong class="tds-num">{{ $valeur }}</strong></span>
              @endforeach
            </div>
          </div>
          <span class="v-card__underlay"></span>
        </div>
      </div>
    @endforeach

    {{-- Par catégorie --}}
    <div class="v-col-lg-7 v-col-12">
      <div class="v-card v-theme--light v-card--variant-elevated tds-carte">
        <div class="v-card-item"><div class="v-card-item__content">
          <div class="v-card-title">Catégories</div>
          <div class="v-card-subtitle">Vêtements déposés, disponibles, réservés et donnés par catégorie</div>
        </div></div>
        <div class="v-table v-theme--light v-table--density-compact">
          <div class="v-table__wrapper">
            <table>
              <thead><tr><th>Catégorie</th><th class="text-end">Déposés</th><th class="text-end">Disponibles</th><th class="text-end">Réservés</th><th class="text-end">Donnés</th></tr></thead>
              <tbody>
                @forelse ($rapport['par_categorie'] as $categorie)
                  <tr>
                    <td><span class="tds-pastille" style="background: {{ $categorie->couleur }}"></span>{{ $categorie->nom }}</td>
                    <td class="text-end tds-num"><strong>{{ $categorie->vetements_count }}</strong></td>
                    <td class="text-end tds-num">{{ $categorie->disponibles_count }}</td>
                    <td class="text-end tds-num">{{ $categorie->reserves_count }}</td>
                    <td class="text-end tds-num">{{ $categorie->donnes_count }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-medium-emphasis">Aucune catégorie pour le moment.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    {{-- Seconde vie --}}
    <div class="v-col-lg-5 v-col-12">
      <div class="v-card v-theme--light v-card--variant-elevated tds-carte">
        <div class="v-card-item"><div class="v-card-item__content">
          <div class="v-card-title">Seconde vie</div>
          <div class="v-card-subtitle">Où partent les vêtements, et ce que la planète y gagne</div>
        </div></div>
        <div class="v-card-text">
          @php $maxOrientation = max(1, max($rapport['orientation'])); @endphp
          @foreach ($rapport['orientation'] as $libelle => $valeur)
            <div class="tds-ligne">
              <span>{{ $libelle }}</span>
              <div class="tds-piste"><div class="tds-remplissage" style="width: {{ 100 * $valeur / $maxOrientation }}%; background: rgb(var(--v-theme-success))"></div></div>
              <span class="tds-ligne__valeur">{{ $valeur }}</span>
            </div>
          @endforeach
          <hr class="my-5" style="opacity: .12">
          <div class="tds-impact">
            <div><strong>{{ $nombre($rapport['impact']['vetements']) }}</strong><span class="tds-sous">vêtement(s) sauvé(s)</span></div>
            <div><strong>{{ $nombre($rapport['impact']['eau']) }}</strong><span class="tds-sous">litres d'eau économisés</span></div>
            <div><strong>{{ $nombre($rapport['impact']['co2']) }}</strong><span class="tds-sous">kg de CO₂ évités</span></div>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    {{-- Répartitions --}}
    @foreach ([
        ['Par état', $rapport['par_etat']],
        ['Par genre', $rapport['par_genre']],
        ['Par taille', $rapport['par_taille']],
        ['Créneaux de retrait choisis', $rapport['par_creneau']],
    ] as [$titre, $lignes])
      @php $maxLigne = max(1, max($lignes ?: [0])); @endphp
      <div class="v-col-lg-3 v-col-sm-6 v-col-12">
        <div class="v-card v-theme--light v-card--variant-elevated tds-carte">
          <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">{{ $titre }}</div></div></div>
          <div class="v-card-text">
            @foreach ($lignes as $libelle => $valeur)
              <div class="tds-ligne">
                <span class="text-truncate">{{ $libelle }}</span>
                <div class="tds-piste"><div class="tds-remplissage" style="width: {{ 100 * $valeur / $maxLigne }}%"></div></div>
                <span class="tds-ligne__valeur">{{ $valeur }}</span>
              </div>
            @endforeach
          </div>
          <span class="v-card__underlay"></span>
        </div>
      </div>
    @endforeach

    {{-- Meilleurs déposants --}}
    <div class="v-col-12">
      <div class="v-card v-theme--light v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content">
          <div class="v-card-title">Meilleurs déposants</div>
          <div class="v-card-subtitle">Les 5 membres qui ont déposé le plus de vêtements</div>
        </div></div>
        <div class="v-table v-theme--light v-table--density-compact">
          <div class="v-table__wrapper">
            <table>
              <thead><tr><th style="width: 3rem">#</th><th>Membre</th><th>E-mail</th><th class="text-end">Vêtements déposés</th></tr></thead>
              <tbody>
                @forelse ($rapport['top_deposants'] as $deposant)
                  <tr>
                    <td class="tds-num">{{ $loop->iteration }}</td>
                    <td>{{ $deposant->name }}</td>
                    <td class="text-medium-emphasis">{{ $deposant->email }}</td>
                    <td class="text-end tds-num"><strong>{{ $deposant->vetements_count }}</strong></td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-medium-emphasis">Aucun dépôt pour le moment.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  </div>

  <p class="tds-sous mt-4">Calculé le {{ $rapport['genere_le']->format('d/m/Y à H:i') }}.</p>
@endsection
