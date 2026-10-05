@extends('layouts.back')

@section('title', 'Dons')

@include('admin.dons.partials.styles')

@section('content')
@php($hasFilters = $filters['q'] !== '' || $filters['association_id'] || $filters['statut'] || $filters['type_article'])

<div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
  <div>
    <h4 class="text-h4 mb-1">Dons aux associations</h4>
    <p class="mb-0 text-medium-emphasis">Suivez les dons proposés par les utilisateurs et mettez à jour leur statut.</p>
  </div>
  <div class="d-flex gap-2">
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.associations.index'), 'label' => 'Associations', 'variant' => 'tonal', 'icon' => 'bx-buildings'])
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.create'), 'label' => 'Nouveau don', 'icon' => 'bx-plus'])
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
  <div class="v-card-text">
    <form method="GET" action="{{ route('admin.dons.dons.index') }}" class="tcd-filters">
      <div>
        <label for="q" class="tcd-label">Rechercher</label>
        <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="tcd-input" placeholder="Donateur, association, message…">
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
          @foreach (\App\Models\Don::STATUTS as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected($filters['statut'] === $optValue)>{{ $optLabel }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="type_article" class="tcd-label">Type d'article</label>
        <select id="type_article" name="type_article" class="tcd-input">
          <option value="">Tous</option>
          @foreach (\App\Models\Don::TYPES as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected($filters['type_article'] === $optValue)>{{ $optLabel }}</option>
          @endforeach
        </select>
      </div>
      <div class="tcd-filters-actions">
        @include('admin.dons.partials.btn', ['label' => 'Filtrer', 'icon' => 'bx-filter-alt'])
        @if ($hasFilters)
          @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.index'), 'label' => 'Réinitialiser', 'variant' => 'tonal', 'color' => 'secondary'])
        @endif
      </div>
    </form>
  </div>

  <div class="v-table v-theme--light v-table--density-default tcd-table">
    <div class="v-table__wrapper">
      <table>
        <thead>
          <tr>
            <th>N°</th>
            <th>Association</th>
            <th>Donateur</th>
            <th>Article</th>
            <th class="text-center">Qté</th>
            <th class="text-end">Poids</th>
            <th>Remise</th>
            <th>Statut</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($dons as $don)
            <tr>
              <td><a href="{{ route('admin.dons.dons.show', $don) }}">#{{ $don->id }}</a></td>
              <td><a href="{{ route('admin.dons.associations.show', $don->association) }}">{{ $don->association->nom }}</a></td>
              <td>
                <div>{{ $don->user->name }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ $don->user->email }}</div>
              </td>
              <td>
                <div>{{ $don->typeLabel() }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ $don->etatLabel() }}</div>
              </td>
              <td class="text-center">{{ $don->quantite }}</td>
              <td class="text-end tcd-nowrap">{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</td>
              <td>
                <div>{{ $don->date_remise->format('d/m/Y') }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ $don->modeLabel() }}</div>
              </td>
              <td>
                <form method="POST" action="{{ route('admin.dons.dons.statut', $don) }}" class="tcd-statut-form d-flex flex-column align-start gap-1">
                  @csrf
                  @method('PATCH')
                  @include('admin.dons.partials.statut', ['don' => $don])
                  <select name="statut" class="tcd-input" aria-label="Changer le statut du don n°{{ $don->id }}" onchange="this.form.submit()">
                    @foreach (\App\Models\Don::STATUTS as $optValue => $optLabel)
                      <option value="{{ $optValue }}" @selected($don->statut === $optValue)>{{ $optLabel }}</option>
                    @endforeach
                  </select>
                  <noscript><button type="submit">OK</button></noscript>
                </form>
              </td>
              <td class="text-end">
                <div class="d-flex justify-end gap-1">
                  @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.show', $don), 'icon' => 'bx-show', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Voir'])
                  @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.edit', $don), 'icon' => 'bx-edit', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Modifier'])
                  <form method="POST" action="{{ route('admin.dons.dons.destroy', $don) }}" class="tcd-inline">
                    @csrf
                    @method('DELETE')
                    @include('admin.dons.partials.btn', ['icon' => 'bx-trash', 'variant' => 'text', 'color' => 'error', 'size' => 'small', 'title' => 'Supprimer', 'confirm' => "Supprimer le don n°{$don->id} ?"])
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-medium-emphasis py-6">Aucun don ne correspond à ces critères.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="v-card-text">
    {{ $dons->links('admin.dons.partials.pagination') }}
  </div>
  <span class="v-card__underlay"></span>
</div>
@endsection
