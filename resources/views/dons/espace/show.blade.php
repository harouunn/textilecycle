@extends('layouts.front')

@section('title', 'Don n°'.$don->id.' — Espace association')

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Espace association" titre="Don n°{{ $don->id }}" sous-titre="Pour {{ $don->association->nom }} — proposé le {{ $don->created_at->format('d/m/Y') }}"
    :fil="['Dons' => route('dons.index'), 'Espace association' => route('dons.espace.index'), 'Don n°'.$don->id => null]">
    <a href="{{ route('dons.espace.index') }}" class="tc-btn">Retour aux dons reçus</a>
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card border-0 bg-light mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="text-uppercase mb-0">Détail</h5>
              @include('dons.partials.statut-badge', ['don' => $don])
            </div>
            <dl class="row mb-0">
              <dt class="col-sm-5">Donateur</dt>
              <dd class="col-sm-7">{{ $don->user->name }} — <a href="mailto:{{ $don->user->email }}">{{ $don->user->email }}</a></dd>
              <dt class="col-sm-5">Article</dt><dd class="col-sm-7">{{ $don->typeLabel() }} · {{ $don->etatLabel() }}</dd>
              <dt class="col-sm-5">Quantité</dt><dd class="col-sm-7">{{ $don->quantite }} article(s)</dd>
              <dt class="col-sm-5">Poids estimé</dt><dd class="col-sm-7">{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</dd>
              <dt class="col-sm-5">Remise</dt>
              <dd class="col-sm-7">{{ $don->modeLabel() }}, {{ $don->creneau ? $don->creneau->libelle() : 'le '.$don->date_remise->format('d/m/Y') }}</dd>
              @if ($don->campagne)
                <dt class="col-sm-5">Campagne</dt><dd class="col-sm-7">{{ $don->campagne->titre }}</dd>
              @endif
              @if ($don->adresse_collecte)
                <dt class="col-sm-5">Adresse de collecte</dt><dd class="col-sm-7">{{ $don->adresse_collecte }}</dd>
              @endif
              <dt class="col-sm-5">Message</dt><dd class="col-sm-7" style="white-space: pre-line">{{ $don->message ?: '—' }}</dd>
            </dl>
          </div>
        </div>

        <div class="card border-0 bg-light">
          <div class="card-body">
            <h5 class="text-uppercase mb-3">Traiter ce don</h5>
            @include('dons.espace._actions', ['don' => $don, 'commentaire' => true, 'taille' => ''])
            @can('telechargerRecu', $don)
              <a href="{{ route('dons.mes-dons.recu', $don) }}" class="btn btn-outline-dark mt-3">Reçu {{ $don->reference }} (PDF)</a>
            @endcan
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card border-0 bg-light h-100">
          <div class="card-body">
            <h5 class="text-uppercase mb-3">Historique</h5>
            @include('dons.partials.historique', ['don' => $don, 'afficherAuteurs' => true])
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
