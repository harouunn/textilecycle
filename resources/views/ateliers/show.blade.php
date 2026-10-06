@extends('layouts.front')

@section('title', $atelier->nom)

@section('content')
  @include('ateliers.partials.styles')

  @php
    $details = array_filter([
        'Adresse' => $atelier->adresse,
        'Ville' => $atelier->code_postal.' '.$atelier->ville,
        'Téléphone' => $atelier->telephone,
        'Email' => $atelier->email,
    ]);
  @endphp

  <section class="py-5">
    <div class="container">
      <nav aria-label="Fil d'Ariane" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="{{ route('ateliers.index') }}">Ateliers</a></li>
          <li class="breadcrumb-item active" aria-current="page">{{ $atelier->nom }}</li>
        </ol>
      </nav>

      <div class="row g-5">
        <div class="col-md-6">
          <h1 class="section-title fs-2 mb-4">{{ $atelier->nom }}</h1>
          @if ($atelier->description)
            <p style="white-space: pre-line;">{{ $atelier->description }}</p>
          @endif

          <table class="table mt-4">
            <tbody>
              @foreach ($details as $label => $value)
                <tr>
                  <th scope="row" class="text-uppercase fw-normal" style="width: 35%;">{{ $label }}</th>
                  <td>{{ $value }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <div class="d-flex flex-wrap gap-3 mt-4">
            @auth
              <a href="{{ route('ateliers.demandes.create', $atelier) }}" class="btn btn-dark text-uppercase">Demander une réparation</a>
            @else
              <a href="{{ route('login') }}" class="btn btn-dark text-uppercase">Connectez-vous pour demander une réparation</a>
            @endauth
            <a href="{{ route('ateliers.index') }}" class="btn btn-outline-dark text-uppercase">Retour aux ateliers</a>
          </div>
        </div>

        {{-- Barème utilisé par le diagnostic automatique --}}
        <div class="col-md-6">
          <h4 class="text-uppercase fs-5 mb-3">Tarifs indicatifs</h4>
          <p class="small">
            Prix de base pour un haut ou un pantalon. Robes, jupes et mailles : +20 %, vestes et manteaux : +50 %.
            Le diagnostic de votre demande calcule l'estimation exacte.
          </p>
          <table class="table table-sm">
            <thead>
              <tr class="text-uppercase small">
                <th scope="col">Réparation</th>
                <th scope="col" class="text-end">À partir de</th>
                <th scope="col" class="text-end">Délai</th>
              </tr>
            </thead>
            <tbody>
              @foreach (\App\Enums\Ateliers\TypeReparation::cases() as $reparation)
                @continue($reparation === \App\Enums\Ateliers\TypeReparation::AExpertiser)
                <tr>
                  <td>{{ $reparation->label() }}</td>
                  <td class="text-end">{{ \App\Models\DemandeReparation::formaterMontant($reparation->coutBase()) }}</td>
                  <td class="text-end">{{ $reparation->delaiBase() }} j</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
@endsection
