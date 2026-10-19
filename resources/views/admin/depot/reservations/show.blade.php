@extends('layouts.back')

@section('title', 'Réservation '.$reservation->code)

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  @php
    $statut = $reservation->statut;
    $vetement = $reservation->vetement;
    $chronologie = [
        ['Réservation demandée', $reservation->created_at, true, false],
        ['Créneau confirmé', $reservation->confirmee_le, $reservation->confirmee_le !== null, false],
        ['Vêtement retiré', $reservation->retiree_le, $reservation->retiree_le !== null, false],
    ];
    if ($reservation->annulee_le) {
        $chronologie[] = ['Réservation annulée', $reservation->annulee_le, false, true];
    }
  @endphp

  <div class="tc-page-head">
    <div>
      <h4 class="text-h4 d-flex align-center gap-3">
        Réservation {{ $reservation->code }}
        @include('admin.depot.partials.chip', ['label' => $statut->label(), 'color' => $statut->color()])
      </h4>
      <p>Retrait le {{ $reservation->date_retrait->format('d/m/Y') }}, {{ $reservation->creneau->label() }}</p>
    </div>
    <div class="d-flex flex-wrap gap-3">
      @include('admin.depot.partials.btn', ['label' => 'Bon PDF', 'icon' => 'bx-qr', 'variant' => 'tonal', 'color' => 'secondary', 'href' => route('depot.reservations.bon', $reservation)])
      @include('admin.depot.partials.btn', ['label' => 'Toutes les réservations', 'icon' => 'bx-left-arrow-alt', 'variant' => 'text', 'color' => 'secondary', 'href' => route('admin.depot.reservations.index')])
    </div>
  </div>

  @if ($errors->has('statut'))
    <div class="tc-alert tc-alert--error mb-6" role="alert">{{ $errors->first('statut') }}</div>
  @endif

  <div class="v-row">
    <div class="v-col-md-8 v-col-12">
      {{-- Vêtement réservé --}}
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Vêtement réservé</div></div></div>
        <div class="v-card-text d-flex flex-wrap gap-6">
          <img src="{{ $vetement->miniature_url }}" alt="{{ $vetement->titre }}" style="width: 140px; height: 175px; object-fit: cover; border-radius: 6px;">
          <div class="flex-grow-1">
            <a href="{{ route('admin.depot.vetements.show', $vetement) }}" class="text-h6 text-high-emphasis">{{ $vetement->titre }}</a>
            <div class="d-flex align-center gap-2 text-body-2 my-2"><span class="tc-swatch" style="background: {{ $vetement->categorie->couleur }}"></span>{{ $vetement->categorie->nom }}</div>
            <dl class="tc-dl">
              <dt>Taille / état</dt><dd>{{ $vetement->taille->label() }} · {{ $vetement->etat->label() }}</dd>
              <dt>Statut du vêtement</dt><dd>@include('admin.depot.partials.chip', ['label' => $vetement->statut->label(), 'color' => $vetement->statut->color()])</dd>
              <dt>Déposé par</dt><dd>{{ $vetement->user->name }}</dd>
            </dl>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>

      {{-- Client --}}
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Client</div></div></div>
        <div class="v-card-text">
          <dl class="tc-dl">
            <dt>Nom</dt><dd>{{ $reservation->user->name }}</dd>
            <dt>Email</dt><dd><a href="mailto:{{ $reservation->user->email }}">{{ $reservation->user->email }}</a></dd>
            <dt>Téléphone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $reservation->telephone) }}">{{ $reservation->telephone }}</a></dd>
            <dt>Message</dt><dd style="white-space: pre-line;">{{ $reservation->message ?: '—' }}</dd>
            @if ($reservation->motif_annulation)
              <dt>Motif d'annulation</dt><dd>{{ $reservation->motif_annulation }}</dd>
            @endif
          </dl>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-md-4 v-col-12">
      {{-- Actions --}}
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Traiter la réservation</div></div></div>
        <div class="v-card-text">
          @if ($statut->estActive())
            <div class="d-flex flex-column gap-3 mb-6">
              @if ($statut === \App\Enums\Depot\StatutReservation::EnAttente)
                <form method="POST" action="{{ route('admin.depot.reservations.confirmer', $reservation) }}">
                  @csrf
                  @method('PATCH')
                  @include('admin.depot.partials.btn', ['label' => 'Confirmer le créneau', 'icon' => 'bx-calendar-check', 'color' => 'info'])
                </form>
              @endif
              <form method="POST" action="{{ route('admin.depot.reservations.retirer', $reservation) }}">
                @csrf
                @method('PATCH')
                @include('admin.depot.partials.btn', ['label' => 'Marquer comme retiré', 'icon' => 'bx-package', 'color' => 'success'])
              </form>
            </div>

            <form method="POST" action="{{ route('admin.depot.reservations.annuler', $reservation) }}">
              @csrf
              @method('PATCH')
              <label for="motif_annulation" class="tc-label">Motif d'annulation *</label>
              <textarea id="motif_annulation" name="motif_annulation" required maxlength="500" placeholder="ex. Le client n'est pas venu."
                @class(['tc-control mb-3', 'is-invalid' => $errors->has('motif_annulation')]) style="min-height: 80px;">{{ old('motif_annulation') }}</textarea>
              @error('motif_annulation')
                <div class="tc-error mb-3">{{ $message }}</div>
              @enderror
              @include('admin.depot.partials.btn', ['label' => 'Annuler la réservation', 'icon' => 'bx-x-circle', 'variant' => 'tonal', 'color' => 'error'])
            </form>
          @else
            <p class="text-body-2 text-medium-emphasis mb-4">Cette réservation est terminée ({{ Str::lower($statut->label()) }}).</p>
          @endif

          <hr class="my-6" style="border-color: rgba(var(--v-border-color), var(--v-border-opacity));">
          <form method="POST" action="{{ route('admin.depot.reservations.destroy', $reservation) }}" onsubmit="return confirm(@js("Supprimer la réservation {$reservation->code} ?"))">
            @csrf
            @method('DELETE')
            @include('admin.depot.partials.btn', ['label' => 'Supprimer', 'icon' => 'bx-trash', 'variant' => 'text', 'color' => 'error'])
          </form>
        </div>
        <span class="v-card__underlay"></span>
      </div>

      {{-- Chronologie --}}
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Chronologie</div></div></div>
        <div class="v-card-text">
          <ol class="tc-timeline">
            @foreach ($chronologie as [$etape, $date, $fait, $annule])
              <li @class(['fait' => $fait, 'annule' => $annule])>
                <div class="font-weight-medium">{{ $etape }}</div>
                <div class="text-body-2 text-disabled">{{ $date ? $date->format('d/m/Y à H:i') : 'À venir' }}</div>
              </li>
            @endforeach
          </ol>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  </div>
@endsection
