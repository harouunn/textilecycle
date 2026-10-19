@extends('layouts.front')

@section('title', $vetement->titre)

@section('content')

  @php
    $attributs = [
        'Taille' => $vetement->taille->label(),
        'Pour' => $vetement->genre->label(),
        'Matière' => $vetement->matiere,
        'État' => $vetement->etat->label(),
        'Déposé le' => $vetement->date_depot->format('d/m/Y'),
    ];
  @endphp

  <div class="tc-page">
    <section class="py-5">
      <div class="container">
        <nav class="tc-fil mb-4" aria-label="Fil d'Ariane">
          <a href="{{ route('depot.catalogue.index') }}">Catalogue</a> <span aria-hidden="true">/</span>
          <a href="{{ route('depot.catalogue.index', ['categorie' => $vetement->categorie_id]) }}">{{ $vetement->categorie->nom }}</a> <span aria-hidden="true">/</span>
          <span aria-current="page">{{ $vetement->titre }}</span>
        </nav>

        @include('depot.partials.flash')

        @if ($vetement->moderation !== \App\Enums\Depot\Moderation::Approuve)
          <div class="tc-alerte tc-alerte--info" role="status">
            <x-ui.icone nom="info" />
            <div>Seul vous voyez cette page : votre vêtement est « {{ $vetement->moderation->label() }} » et n'apparaît pas encore dans le catalogue.</div>
          </div>
        @endif

        <div class="row g-5">
          <div class="col-lg-6">
            <img src="{{ $vetement->photo_url }}" alt="{{ $vetement->titre }}" class="tc-fiche__photo" decoding="async">
          </div>

          <div class="col-lg-6">
            <span class="tc-pastille mb-3"><i style="background: {{ $vetement->categorie->couleur }}"></i>{{ $vetement->categorie->nom }}</span>
            <h1 style="font-family: 'Marcellus', serif; font-size: clamp(1.8rem, 3vw, 2.4rem);" class="mb-3">{{ $vetement->titre }}</h1>
            <x-ui.badge :couleur="$vetement->statut->color()" class="mb-4">{{ $vetement->statut->label() }}</x-ui.badge>

            <p class="mb-4" style="white-space: pre-line;">{{ $vetement->description }}</p>

            <dl class="tc-attributs mb-4">
              @foreach ($attributs as $libelle => $valeur)
                <div><dt>{{ $libelle }}</dt><dd>{{ $valeur }}</dd></div>
              @endforeach
            </dl>

            {{-- Impact écologique --}}
            <div class="tc-alerte tc-alerte--succes mb-4" style="border-left-color: var(--tc-vert);">
              <x-ui.icone nom="feuille" />
              <div>
                <strong>Un choix responsable.</strong> Porter ce vêtement plutôt qu'un neuf évite environ
                <strong>{{ \App\Services\Depot\ImpactEcologique::formaterEau($impact['eau']) }} d'eau</strong>
                et <strong>{{ $impact['co2'] }} kg de CO₂</strong>.
              </div>
            </div>

            {{-- Réservation --}}
            <div class="tc-carte tc-carte__corps">
              @if ($reservable)
                <h2 class="fs-6 text-uppercase mb-2" style="letter-spacing: .08em;">Ce vêtement vous plaît ?</h2>
                <p class="small mb-3">Réservez-le gratuitement, puis venez le retirer dans les 14 jours, au créneau de votre choix.</p>
                @auth
                  <a href="{{ route('depot.reservations.create', $vetement) }}" class="tc-btn tc-btn--vert tc-btn--bloc"><x-ui.icone nom="calendrier" /> Réserver ce vêtement</a>
                @else
                  <a href="{{ route('login') }}" class="tc-btn tc-btn--bloc">Se connecter pour réserver</a>
                @endauth
              @elseif (auth()->id() === $vetement->user_id)
                <p class="mb-3"><strong class="text-dark">C'est votre dépôt.</strong> Vous pouvez le modifier depuis « Mes dépôts ».</p>
                <a href="{{ route('depot.mes-depots.index') }}" class="tc-btn tc-btn--clair tc-btn--bloc">Voir mes dépôts</a>
              @else
                <p class="mb-0 d-flex gap-2 align-items-center"><x-ui.icone nom="cadenas" style="width: 18px; height: 18px;" /> Ce vêtement n'est plus disponible à la réservation.</p>
              @endif
            </div>

            <a href="{{ route('depot.catalogue.index') }}" class="tc-btn tc-btn--clair mt-3"><x-ui.icone nom="retour" /> Retour au catalogue</a>
          </div>
        </div>

        @if ($similaires->isNotEmpty())
          <div class="mt-5 pt-5 border-top">
            <div class="tc-surtitre mb-2">Dans la même catégorie</div>
            <h2 style="font-family: 'Marcellus', serif;" class="mb-4">Vous aimerez aussi</h2>
            <div class="row g-4">
              @foreach ($similaires as $similaire)
                <div class="col-6 col-md-3">
                  <x-depot.carte-vetement :vetement="$similaire" />
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    </section>
  </div>
@endsection
