@extends('layouts.back')

@section('title', 'Campagnes de collecte')

@include('admin.dons.partials.styles')

@section('content')
@php($hasFilters = $filters['q'] !== '' || $filters['association_id'] || $filters['statut'])

<div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
  <div>
    <h4 class="text-h4 mb-1">Campagnes de collecte</h4>
    <p class="mb-0 text-medium-emphasis">Objectifs de collecte des associations, avec leur progression (kilos reçus).</p>
  </div>
  <div class="d-flex gap-2">
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.tableau-de-bord'), 'label' => 'Tableau de bord', 'variant' => 'tonal', 'icon' => 'bx-bar-chart-alt-2'])
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.campagnes.create'), 'label' => 'Nouvelle campagne', 'icon' => 'bx-plus'])
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
  <div class="v-card-text">
    <form method="GET" action="{{ route('admin.dons.campagnes.index') }}" class="tcd-filters">
      <div>
        <label for="q" class="tcd-label">Rechercher</label>
        <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="tcd-input" placeholder="Titre">
      </div>
      <div>
        <label for="association_id" class="tcd-label">Association</label>
        <select id="association_id" name="association_id" class="tcd-input">
          <option value="">Toutes</option>
          @foreach ($associations as $id => $nom)
            <option value="{{ $id }}" @selected((string) $filters['association_id'] === (string) $id)>{{ $nom }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="statut" class="tcd-label">Statut</label>
        <select id="statut" name="statut" class="tcd-input">
          <option value="">Tous</option>
          @foreach (\App\Enums\Dons\StatutCampagne::options() as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected($filters['statut'] === $valeur)>{{ $libelle }}</option>
          @endforeach
        </select>
      </div>
      <div class="tcd-filters-actions">
        @include('admin.dons.partials.btn', ['label' => 'Filtrer', 'icon' => 'bx-filter-alt'])
        @if ($hasFilters)
          @include('admin.dons.partials.btn', ['href' => route('admin.dons.campagnes.index'), 'label' => 'Réinitialiser', 'variant' => 'tonal', 'color' => 'secondary'])
        @endif
      </div>
    </form>
  </div>

  <div class="v-table v-theme--light v-table--density-default tcd-table">
    <div class="v-table__wrapper">
      <table>
        <thead>
          <tr><th>Campagne</th><th>Association</th><th>Période</th><th>Progression</th><th>Statut</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          @forelse ($campagnes as $campagne)
            <tr>
              <td><a href="{{ route('admin.dons.campagnes.show', $campagne) }}">{{ $campagne->titre }}</a></td>
              <td>{{ $campagne->association->nom }}</td>
              <td class="tcd-nowrap">{{ $campagne->date_debut->format('d/m/Y') }} → {{ $campagne->date_fin->format('d/m/Y') }}</td>
              <td style="min-width: 180px">
                @include('admin.dons.partials.progression', ['campagne' => $campagne])
              </td>
              <td>@include('admin.dons.partials.statut-campagne', ['statut' => $campagne->statut])</td>
              <td class="text-end">
                <div class="d-flex justify-end gap-1">
                  @include('admin.dons.partials.btn', ['href' => route('admin.dons.campagnes.edit', $campagne), 'icon' => 'bx-edit', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Modifier'])
                  <form method="POST" action="{{ route('admin.dons.campagnes.destroy', $campagne) }}" class="tcd-inline">
                    @csrf
                    @method('DELETE')
                    @include('admin.dons.partials.btn', ['icon' => 'bx-trash', 'variant' => 'text', 'color' => 'error', 'size' => 'small', 'title' => 'Supprimer', 'confirm' => "Supprimer la campagne « {$campagne->titre} » ? Ses dons sont conservés."])
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-medium-emphasis py-6">Aucune campagne ne correspond à ces critères.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="v-card-text">{{ $campagnes->links('admin.dons.partials.pagination') }}</div>
  <span class="v-card__underlay"></span>
</div>
@endsection
