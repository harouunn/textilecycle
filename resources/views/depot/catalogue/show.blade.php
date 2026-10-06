@extends('layouts.front')

@section('title', $vetement->titre)

@section('content')
  @include('depot.partials.styles')

  @php
    $details = [
        'Catégorie' => $vetement->categorie->nom,
        'Taille' => $vetement->taille->label(),
        'Genre' => $vetement->genre->label(),
        'Matière' => $vetement->matiere,
        'État' => $vetement->etat->label(),
        'Déposé le' => $vetement->date_depot->format('d/m/Y'),
    ];
  @endphp

  <section class="py-5">
    <div class="container">
      <nav aria-label="Fil d'Ariane" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="{{ route('depot.catalogue.index') }}">Catalogue</a></li>
          <li class="breadcrumb-item"><a href="{{ route('depot.catalogue.index', ['categorie' => $vetement->categorie_id]) }}">{{ $vetement->categorie->nom }}</a></li>
          <li class="breadcrumb-item active" aria-current="page">{{ $vetement->titre }}</li>
        </ol>
      </nav>

      <div class="row g-5">
        <div class="col-md-6">
          <img src="{{ $vetement->photo_url }}" alt="{{ $vetement->titre }}" class="tc-vetement-photo">
        </div>

        <div class="col-md-6">
          <span class="badge text-bg-{{ $vetement->statut->color() }} mb-3">{{ $vetement->statut->label() }}</span>
          <h1 class="section-title fs-2 mb-4">{{ $vetement->titre }}</h1>
          <p style="white-space: pre-line;">{{ $vetement->description }}</p>

          <table class="table mt-4">
            <tbody>
              @foreach ($details as $label => $value)
                <tr>
                  <th scope="row" class="text-uppercase fw-normal" style="width: 40%;">{{ $label }}</th>
                  <td>{{ $value }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <a href="{{ route('depot.catalogue.index') }}" class="btn btn-outline-dark text-uppercase mt-3">Retour au catalogue</a>
        </div>
      </div>

      @if ($similaires->isNotEmpty())
        <h4 class="text-uppercase mt-5 pt-5 mb-4">Dans la même catégorie</h4>
        <div class="row g-4">
          @foreach ($similaires as $similaire)
            <div class="col-6 col-md-3">
              @include('depot.partials.vetement-card', ['vetement' => $similaire])
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>
@endsection
