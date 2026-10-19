@extends('layouts.front')

@section('title', 'Réserver « '.$vetement->titre.' »')

@section('content')

  <div class="tc-page">
    <x-ui.hero
      surtitre="Réservation"
      titre="Réserver ce vêtement"
      sous-titre="Choisissez quand vous viendrez le retirer. La réservation est gratuite et vous pouvez l'annuler à tout moment."
      :fil="['Catalogue' => route('depot.catalogue.index'), $vetement->titre => route('depot.catalogue.show', $vetement), 'Réserver' => null]" />

    <section class="py-5">
      <div class="container">
        <div class="row g-5">
          <div class="col-lg-7">
            @include('depot.partials.flash')

            <form method="POST" action="{{ route('depot.reservations.store', $vetement) }}" class="tc-form tc-carte tc-carte__corps" novalidate>
              @csrf

              <div class="tc-section-form">
                <h3><span>1</span> Date et créneau de retrait</h3>
                <div class="mb-4">
                  <label for="date_retrait" class="form-label">Date de retrait *</label>
                  <input id="date_retrait" type="date" name="date_retrait" required
                    value="{{ old('date_retrait', $reservation->date_retrait?->format('Y-m-d')) }}"
                    min="{{ today()->addDay()->format('Y-m-d') }}"
                    max="{{ today()->addDays(\App\Http\Requests\Depot\StoreReservationRequest::DELAI_MAX_JOURS)->format('Y-m-d') }}"
                    @class(['form-control', 'is-invalid' => $errors->has('date_retrait')])>
                  <div class="form-text">Entre demain et dans 14 jours.</div>
                  @error('date_retrait')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <fieldset>
                  <legend class="form-label">Créneau *</legend>
                  <div class="tc-choix">
                    @foreach (\App\Enums\Depot\Creneau::cases() as $creneau)
                      <div>
                        <input type="radio" id="creneau-{{ $creneau->value }}" name="creneau" value="{{ $creneau->value }}"
                          @checked(old('creneau', $reservation->creneau?->value) === $creneau->value)>
                        <label for="creneau-{{ $creneau->value }}">
                          <x-ui.icone nom="horloge" style="width: 18px; height: 18px;" class="me-1" /> {{ $creneau->label() }}
                        </label>
                      </div>
                    @endforeach
                  </div>
                  @error('creneau')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </fieldset>
              </div>

              <div class="tc-section-form">
                <h3><span>2</span> Vos coordonnées</h3>
                <div class="mb-4">
                  <label for="telephone" class="form-label">Téléphone *</label>
                  <input id="telephone" type="tel" name="telephone" required maxlength="20" autocomplete="tel" placeholder="ex. 22 123 456"
                    value="{{ old('telephone', $reservation->telephone) }}"
                    @class(['form-control', 'is-invalid' => $errors->has('telephone')])>
                  <div class="form-text">Pour vous prévenir si le rendez-vous change.</div>
                  @error('telephone')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div>
                  <label for="message" class="form-label">Message (facultatif)</label>
                  <textarea id="message" name="message" rows="3" maxlength="500" placeholder="Une précision pour l'équipe ?"
                    @class(['form-control', 'is-invalid' => $errors->has('message')])>{{ old('message', $reservation->message) }}</textarea>
                  @error('message')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="d-flex flex-wrap gap-3">
                <button type="submit" class="tc-btn tc-btn--vert"><x-ui.icone nom="valide" /> Confirmer la réservation</button>
                <a href="{{ route('depot.catalogue.show', $vetement) }}" class="tc-btn tc-btn--clair">Annuler</a>
              </div>
            </form>
          </div>

          {{-- Récapitulatif --}}
          <aside class="col-lg-5">
            <div class="tc-carte">
              <div class="d-flex gap-3 p-3 border-bottom">
                <img src="{{ $vetement->miniature_url }}" alt="" class="tc-vignette" style="width: 88px; height: 110px;">
                <div>
                  <span class="tc-pastille mb-1"><i style="background: {{ $vetement->categorie->couleur }}"></i>{{ $vetement->categorie->nom }}</span>
                  <h2 class="fs-5 mb-1">{{ $vetement->titre }}</h2>
                  <div class="small">Taille {{ $vetement->taille->label() }} · {{ $vetement->etat->label() }}</div>
                </div>
              </div>
              <div class="tc-carte__corps">
                <h3 class="fs-6 text-uppercase mb-3" style="letter-spacing: .08em;">Comment ça marche</h3>
                <ol class="tc-numeros">
                  <li><strong>Vous réservez</strong> Le vêtement est retiré du catalogue pour vous.</li>
                  <li><strong>Nous confirmons</strong> L'équipe valide votre créneau.</li>
                  <li><strong>Vous le retirez</strong> Présentez votre code de réservation sur place.</li>
                </ol>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </section>
  </div>
@endsection
