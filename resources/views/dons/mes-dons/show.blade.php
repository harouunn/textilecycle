@extends('layouts.front')

@section('title', 'Mon don n°'.$don->id)

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Mon espace" titre="Don n°{{ $don->id }}" sous-titre="À {{ $don->association->nom }} — proposé le {{ $don->created_at->format('d/m/Y') }}"
    :fil="['Dons' => route('dons.index'), 'Mes dons' => route('dons.mes-dons'), 'Don n°'.$don->id => null]">
    <a href="{{ route('dons.mes-dons') }}" class="tc-btn">Retour à mes dons</a>
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card border-0 bg-light h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="text-uppercase mb-0">Détail</h5>
              @include('dons.partials.statut-badge', ['don' => $don])
            </div>
            <dl class="row mb-0">
              <dt class="col-sm-5">Association</dt>
              <dd class="col-sm-7">
                @if ($don->association->active && ! $don->association->trashed())
                  <a href="{{ route('dons.associations.show', $don->association) }}">{{ $don->association->nom }}</a>
                @else
                  {{ $don->association->nom }}
                @endif
              </dd>
              <dt class="col-sm-5">Article</dt><dd class="col-sm-7">{{ $don->typeLabel() }} · {{ $don->etatLabel() }}</dd>
              <dt class="col-sm-5">Quantité</dt><dd class="col-sm-7">{{ $don->quantite }} article(s)</dd>
              <dt class="col-sm-5">Poids estimé</dt><dd class="col-sm-7">{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</dd>
              @if ($don->campagne)
                <dt class="col-sm-5">Campagne</dt><dd class="col-sm-7">{{ $don->campagne->titre }}</dd>
              @endif
              <dt class="col-sm-5">Remise</dt>
              <dd class="col-sm-7">{{ $don->modeLabel() }}, {{ $don->creneau ? $don->creneau->libelle() : 'le '.$don->date_remise->format('d/m/Y') }}</dd>
              @if ($don->reference)
                <dt class="col-sm-5">Référence</dt><dd class="col-sm-7">{{ $don->reference }}</dd>
              @endif
              @if ($don->adresse_collecte)
                <dt class="col-sm-5">Adresse de collecte</dt><dd class="col-sm-7">{{ $don->adresse_collecte }}</dd>
              @endif
              <dt class="col-sm-5">Message</dt><dd class="col-sm-7" style="white-space: pre-line">{{ $don->message ?: '—' }}</dd>
            </dl>

            <div class="d-flex flex-wrap gap-2 mt-4">
              @can('telechargerRecu', $don)
                <a href="{{ route('dons.mes-dons.recu', $don) }}" class="btn btn-dark rounded-pill">Télécharger le reçu (PDF)</a>
              @endcan
              @can('cancel', $don)
                <form method="POST" action="{{ route('dons.cancel', $don) }}" onsubmit="return confirm('Annuler ce don ?')">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-outline-danger rounded-pill">Annuler ce don</button>
                </form>
              @endcan
            </div>
          </div>
        </div>
      </div>

      @if ($don->poids_kg)
        <div class="col-12 order-lg-last">
          <div class="card border-0">
            <div class="card-body px-0">
              <h5 class="text-uppercase mb-3">Impact de ce don</h5>
              @include('dons.partials.impact', ['impact' => $impact, 'estime' => $impact['estime']])
            </div>
          </div>
        </div>
      @endif

      <div class="col-lg-5">
        <div class="card border-0 bg-light h-100">
          <div class="card-body">
            <h5 class="text-uppercase mb-3">Suivi</h5>
            @include('dons.partials.historique', ['don' => $don])
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
