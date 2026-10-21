@extends('layouts.back')

@section('title', $campagne->titre)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.campagnes.index') }}" class="text-body-2">‹ Retour aux campagnes</a>
  <div class="d-flex flex-wrap align-center justify-space-between gap-4 mt-2">
    <div class="d-flex align-center gap-3">
      <h4 class="text-h4 mb-0">{{ $campagne->titre }}</h4>
      @include('admin.dons.partials.statut-campagne', ['statut' => $campagne->statut])
    </div>
    <div class="d-flex gap-2">
      @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.index', ['campagne_id' => $campagne->id]), 'label' => 'Dons de la campagne', 'variant' => 'tonal', 'icon' => 'bx-list-ul'])
      @include('admin.dons.partials.btn', ['href' => route('admin.dons.campagnes.edit', $campagne), 'label' => 'Modifier', 'icon' => 'bx-edit'])
    </div>
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-row">
  <div class="v-col-md-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Progression</div></div></div>
      <div class="v-card-text">
        @include('admin.dons.partials.progression', ['campagne' => $campagne])
        <p class="tcd-hint mt-2 mb-0">Seuls les dons « Reçu » et « Distribué » comptent.</p>
      </div>
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Description</div></div></div>
      <div class="v-card-text tcd-pre">{{ $campagne->description }}</div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
  <div class="v-col-md-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
      <div class="v-card-text">
        @if ($campagne->image_url)
          <img src="{{ $campagne->image_url }}" alt="" class="mb-4" style="width: 100%; border-radius: 6px">
        @endif
        <dl class="tcd-dl">
          <dt>Association</dt><dd>{{ $campagne->association->nom }}</dd>
          <dt>Période</dt><dd>{{ $campagne->date_debut->format('d/m/Y') }} → {{ $campagne->date_fin->format('d/m/Y') }}</dd>
          <dt>Objectif</dt><dd>{{ number_format($campagne->objectif_kg, 0, ',', ' ') }} kg</dd>
          <dt>Articles ciblés</dt><dd>{{ collect($campagne->typesCibles())->map->label()->join(', ') ?: 'Tous' }}</dd>
        </dl>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  <div class="v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Dons rattachés</div></div></div>
      <div class="v-table v-theme--light v-table--density-default tcd-table">
        <div class="v-table__wrapper">
          <table>
            <thead><tr><th>N°</th><th>Donateur</th><th>Article</th><th class="text-end">Poids</th><th>Statut</th></tr></thead>
            <tbody>
              @forelse ($dons as $don)
                <tr>
                  <td><a href="{{ route('admin.dons.dons.show', $don) }}">#{{ $don->id }}</a></td>
                  <td>{{ $don->user->name }}</td>
                  <td>{{ $don->quantite }} × {{ $don->typeLabel() }}</td>
                  <td class="text-end tcd-nowrap">{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</td>
                  <td>@include('admin.dons.partials.statut', ['don' => $don])</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-medium-emphasis py-6">Aucun don rattaché pour le moment.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="v-card-text">{{ $dons->links('admin.dons.partials.pagination') }}</div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
