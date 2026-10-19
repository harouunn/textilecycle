@extends('layouts.back')

@section('title', 'Réservations')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  @php
    $statutFiltre = $filters['statut'] ?? null;
    $lien = fn (?string $statut) => route('admin.depot.reservations.index', array_filter(['statut' => $statut]));
  @endphp

  <div class="tc-page-head">
    <div>
      <h4 class="text-h4">Réservations</h4>
      <p>Les vêtements réservés par les clients : confirmez le créneau, puis marquez le retrait.</p>
    </div>
    @include('admin.depot.partials.btn', ['label' => 'Exporter (Excel)', 'icon' => 'bx-download', 'variant' => 'tonal', 'color' => 'secondary', 'href' => route('admin.depot.export.reservations', array_filter($filters))])
  </div>

  @include('admin.depot.partials.kpis', ['kpis' => [
      ['Toutes', $stats['total'], 'bx-calendar', 'primary', $lien(null), ! $statutFiltre],
      ['En attente', $stats['en_attente'], 'bx-time-five', 'warning', $lien('en_attente'), $statutFiltre === 'en_attente'],
      ['Confirmées', $stats['confirmee'], 'bx-calendar-check', 'info', $lien('confirmee'), $statutFiltre === 'confirmee'],
      ['Retirées', $stats['retiree'], 'bx-package', 'success', $lien('retiree'), $statutFiltre === 'retiree'],
      ['Annulées', $stats['annulee'], 'bx-x-circle', 'secondary', $lien('annulee'), $statutFiltre === 'annulee'],
  ]])

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">{{ $reservations->total() }} réservation(s)</div>
        <div class="v-card-subtitle">Triées par date de retrait</div>
      </div>
    </div>

    <div class="v-card-text">
      <form method="GET" action="{{ route('admin.depot.reservations.index') }}">
        <div class="v-row">
          <div class="v-col-md-6 v-col-12">
            <label for="search" class="tc-label">Recherche</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Code, vêtement ou client…" class="tc-control">
          </div>
          <div class="v-col-md-3 v-col-sm-6 v-col-12">
            <label for="statut" class="tc-label">Statut</label>
            <select id="statut" name="statut" class="tc-control">
              <option value="">Tous</option>
              @foreach (\App\Enums\Depot\StatutReservation::options() as $value => $label)
                <option value="{{ $value }}" @selected($statutFiltre === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="v-col-md-3 v-col-sm-6 v-col-12 d-flex align-end gap-2">
            @include('admin.depot.partials.btn', ['label' => 'Filtrer', 'icon' => 'bx-filter', 'variant' => 'tonal'])
            @if (array_filter($filters))
              @include('admin.depot.partials.btn', ['title' => 'Réinitialiser les filtres', 'icon' => 'bx-reset', 'iconOnly' => true, 'variant' => 'text', 'color' => 'secondary', 'href' => route('admin.depot.reservations.index')])
            @endif
          </div>
        </div>
      </form>
    </div>

    <div class="v-table v-theme--light v-table--density-default">
      <div class="v-table__wrapper">
        <table>
          <thead>
            <tr>
              <th class="text-start">Code</th>
              <th class="text-start">Vêtement</th>
              <th class="text-start">Client</th>
              <th class="text-start">Retrait</th>
              <th class="text-start">Statut</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($reservations as $reservation)
              <tr>
                <td><a href="{{ route('admin.depot.reservations.show', $reservation) }}" class="font-weight-medium text-high-emphasis">{{ $reservation->code }}</a></td>
                <td>
                  <div class="d-flex align-center gap-3">
                    <img src="{{ $reservation->vetement->miniature_url }}" alt="" class="tc-thumb" loading="lazy" width="48" height="48">
                    <div>
                      <div class="font-weight-medium">{{ $reservation->vetement->titre }}</div>
                      <div class="text-body-2 text-disabled">{{ $reservation->vetement->categorie->nom }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div>{{ $reservation->user->name }}</div>
                  <div class="text-body-2 text-disabled">{{ $reservation->telephone }}</div>
                </td>
                <td>
                  <div class="tc-num">{{ $reservation->date_retrait->format('d/m/Y') }}</div>
                  <div class="text-body-2 text-disabled">{{ $reservation->creneau->label() }}</div>
                </td>
                <td>@include('admin.depot.partials.chip', ['label' => $reservation->statut->label(), 'color' => $reservation->statut->color()])</td>
                <td class="text-end">
                  @if ($reservation->statut === \App\Enums\Depot\StatutReservation::EnAttente)
                    <form method="POST" action="{{ route('admin.depot.reservations.confirmer', $reservation) }}" class="d-inline">
                      @csrf
                      @method('PATCH')
                      @include('admin.depot.partials.btn', ['title' => 'Confirmer', 'icon' => 'bx-check-circle', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'success'])
                    </form>
                  @endif
                  @include('admin.depot.partials.btn', ['title' => 'Voir et traiter', 'icon' => 'bx-show', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'default', 'href' => route('admin.depot.reservations.show', $reservation)])
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-disabled py-6">Aucune réservation ne correspond à votre recherche.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="v-card-text">
      {{ $reservations->links('admin.depot.partials.pagination') }}
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
