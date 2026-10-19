@extends('layouts.front')

@section('title', 'Mes réservations')

@section('content')

  <div class="tc-page">
    <x-ui.hero
      surtitre="Mon espace"
      titre="Mes réservations"
      sous-titre="Retrouvez les vêtements que vous avez réservés, leur créneau de retrait et leur avancement."
      :fil="['Catalogue' => route('depot.catalogue.index'), 'Mes réservations' => null]">
      <a href="{{ route('depot.catalogue.index') }}" class="tc-btn">Parcourir le catalogue</a>
    </x-ui.hero>

    <section class="py-5">
      <div class="container" style="max-width: 1040px;">
        @include('depot.partials.flash')

        @forelse ($reservations as $reservation)
          @php
            $etapes = ['Demandée' => true, 'Confirmée' => $reservation->confirmee_le !== null, 'Retirée' => $reservation->retiree_le !== null];
          @endphp
          <article class="tc-carte mb-4">
            <div class="d-flex flex-column flex-md-row gap-4 p-4">
              <a href="{{ route('depot.catalogue.show', $reservation->vetement) }}" class="flex-shrink-0">
                <img src="{{ $reservation->vetement->miniature_url }}" alt="" class="tc-vignette" style="width: 112px; height: 140px;" loading="lazy">
              </a>
              <div class="flex-grow-1">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                  <div>
                    <div class="tc-surtitre">Réservation {{ $reservation->code }}</div>
                    <h2 class="fs-5 mb-1">
                      <a href="{{ route('depot.catalogue.show', $reservation->vetement) }}" class="text-decoration-none" style="color: var(--tc-encre);">{{ $reservation->vetement->titre }}</a>
                    </h2>
                    <div class="small">{{ $reservation->vetement->categorie->nom }} · Taille {{ $reservation->vetement->taille->label() }}</div>
                  </div>
                  <x-ui.badge :couleur="$reservation->statut->color()">{{ $reservation->statut->label() }}</x-ui.badge>
                </div>

                <div class="d-flex flex-wrap gap-4 my-3 small">
                  <span class="d-inline-flex align-items-center gap-2"><x-ui.icone nom="calendrier" style="width: 18px; height: 18px;" /> Retrait le <strong class="text-dark">{{ $reservation->date_retrait->locale('fr')->translatedFormat('l j F Y') }}</strong></span>
                  <span class="d-inline-flex align-items-center gap-2"><x-ui.icone nom="horloge" style="width: 18px; height: 18px;" /> {{ $reservation->creneau->label() }}</span>
                </div>

                @if ($reservation->statut === \App\Enums\Depot\StatutReservation::Annulee)
                  <p class="small mb-0"><strong class="text-dark">Annulée :</strong> {{ $reservation->motif_annulation }}</p>
                @else
                  <ol class="tc-etapes" aria-label="Avancement">
                    @foreach ($etapes as $etape => $fait)
                      <li @class(['fait' => $fait])>{{ $etape }}</li>
                    @endforeach
                  </ol>
                @endif

                @if ($reservation->statut->estActive())
                  <div class="d-flex flex-wrap justify-content-md-end gap-2 mt-3">
                    <a href="{{ route('depot.reservations.bon', $reservation) }}" class="tc-btn tc-btn--clair tc-btn--petit"><x-ui.icone nom="qr" /> Bon de réservation (PDF)</a>
                    @can('annuler', $reservation)
                      <form method="POST" action="{{ route('depot.reservations.annuler', $reservation) }}"
                        onsubmit="return confirm(@js("Annuler la réservation {$reservation->code} ?"))">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tc-btn tc-btn--danger tc-btn--petit">Annuler la réservation</button>
                      </form>
                    @endcan
                  </div>
                @endif
              </div>
            </div>
          </article>
        @empty
          <x-ui.vide icone="calendrier" titre="Aucune réservation pour le moment">
            <p>Parcourez le catalogue et réservez un vêtement : il sera mis de côté pour vous.</p>
            <a href="{{ route('depot.catalogue.index') }}" class="tc-btn mt-2">Voir le catalogue</a>
          </x-ui.vide>
        @endforelse

        <div class="mt-4">
          {{ $reservations->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </section>
  </div>
@endsection
