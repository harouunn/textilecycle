@extends('layouts.back')

@section('title', 'Journal IA')

@include('admin.dons.partials.styles')

@section('content')
@php($fmt = fn ($n, $d = 0) => number_format((float) $n, $d, ',', ' '))

<div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
  <div>
    <h4 class="text-h4 mb-1">Journal IA</h4>
    <p class="mb-0 text-medium-emphasis">Appels à Gemini des fonctionnalités « Associations &amp; dons ». En cas d'échec, chaque fonctionnalité bascule sur sa version sans IA.</p>
  </div>
  @include('admin.dons.partials.btn', ['href' => route('admin.dons.tableau-de-bord'), 'label' => 'Tableau de bord', 'variant' => 'tonal', 'icon' => 'bx-bar-chart-alt-2'])
</div>

@unless ($iaConfiguree)
  <div class="v-alert v-theme--light text-warning v-alert--density-default v-alert--variant-tonal mb-6" role="alert">
    <div class="v-alert__underlay"></div>
    <div class="v-alert__content">Aucune clé <code>GEMINI_API_KEY</code> n'est configurée : toutes les fonctionnalités utilisent leur version sans IA.</div>
  </div>
@endunless

<div class="v-row mb-2">
  <div class="v-col-md-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-text">
        <p class="mb-1 text-medium-emphasis">Taux de réussite</p>
        <h4 class="text-h4 mb-0">{{ $tauxReussite === null ? '—' : $fmt($tauxReussite, 1).' %' }}</h4>
        <span class="text-body-2 text-medium-emphasis">sur {{ $fmt($appels) }} appel(s)</span>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-md-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-table v-theme--light v-table--density-compact tcd-table">
        <div class="v-table__wrapper">
          <table>
            <thead><tr><th>Fonctionnalité</th><th class="text-end">Appels</th><th class="text-end">Réussite</th><th class="text-end">Durée moyenne</th></tr></thead>
            <tbody>
              @foreach (\App\Models\AiLog::FONCTIONNALITES as $cle => $libelle)
                @php($ligne = $parFonctionnalite[$cle] ?? null)
                <tr>
                  <td>{{ $libelle }}</td>
                  <td class="text-end">{{ $fmt($ligne?->appels) }}</td>
                  <td class="text-end">{{ $ligne && $ligne->appels ? $fmt($ligne->reussis / $ligne->appels * 100, 1).' %' : '—' }}</td>
                  <td class="text-end">{{ $ligne ? $fmt($ligne->duree_moyenne).' ms' : '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>

<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
  <div class="v-card-text">
    <form method="GET" action="{{ route('admin.dons.journal-ia') }}" class="tcd-filters">
      <div>
        <label for="fonctionnalite" class="tcd-label">Fonctionnalité</label>
        <select id="fonctionnalite" name="fonctionnalite" class="tcd-input">
          <option value="">Toutes</option>
          @foreach (\App\Models\AiLog::FONCTIONNALITES as $cle => $libelle)
            <option value="{{ $cle }}" @selected($filtres['fonctionnalite'] === $cle)>{{ $libelle }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="succes" class="tcd-label">Résultat</label>
        <select id="succes" name="succes" class="tcd-input">
          <option value="">Tous</option>
          <option value="1" @selected($filtres['succes'] === '1')>Réussis</option>
          <option value="0" @selected($filtres['succes'] === '0')>Échecs</option>
        </select>
      </div>
      <div class="tcd-filters-actions">
        @include('admin.dons.partials.btn', ['label' => 'Filtrer', 'icon' => 'bx-filter-alt'])
      </div>
    </form>
  </div>

  <div class="v-table v-theme--light v-table--density-default tcd-table">
    <div class="v-table__wrapper">
      <table>
        <thead><tr><th>Date</th><th>Fonctionnalité</th><th>Compte</th><th class="text-end">Durée</th><th>Résultat</th></tr></thead>
        <tbody>
          @forelse ($journal as $log)
            <tr>
              <td class="tcd-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
              <td>{{ $log->fonctionnaliteLabel() }}</td>
              <td>{{ $log->user?->name ?? '—' }}</td>
              <td class="text-end tcd-nowrap">{{ $fmt($log->duree_ms) }} ms</td>
              <td>
                @if ($log->succes)
                  <span class="text-success">Réussi</span>
                @else
                  <span class="text-error">Échec</span>
                  <div class="text-body-2 text-medium-emphasis">{{ $log->erreur }}</div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-medium-emphasis py-6">Aucun appel enregistré.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="v-card-text">{{ $journal->links('admin.dons.partials.pagination') }}</div>
  <span class="v-card__underlay"></span>
</div>
@endsection
