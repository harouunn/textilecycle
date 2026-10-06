@extends('layouts.back')

@section('title', 'Don n°'.$don->id)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.dons.index') }}" class="text-body-2">‹ Retour aux dons</a>
  <div class="d-flex flex-wrap align-center justify-space-between gap-4 mt-2">
    <div class="d-flex align-center gap-3">
      <h4 class="text-h4 mb-0">Don n°{{ $don->id }}</h4>
      @include('admin.dons.partials.statut', ['don' => $don])
    </div>
    <div class="d-flex gap-2">
      @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.edit', $don), 'label' => 'Modifier', 'icon' => 'bx-edit'])
      <form method="POST" action="{{ route('admin.dons.dons.destroy', $don) }}" class="tcd-inline">
        @csrf
        @method('DELETE')
        @include('admin.dons.partials.btn', ['label' => 'Supprimer', 'icon' => 'bx-trash', 'color' => 'error', 'variant' => 'tonal', 'confirm' => "Supprimer le don n°{$don->id} ?"])
      </form>
    </div>
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-row">
  <div class="v-col-md-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Détail</div></div></div>
      <div class="v-card-text">
        <dl class="tcd-dl">
          <dt>Association</dt>
          <dd><a href="{{ route('admin.dons.associations.show', $don->association) }}">{{ $don->association->nom }}</a> ({{ $don->association->ville }})</dd>
          <dt>Donateur</dt>
          <dd>{{ $don->user->name }} — <a href="mailto:{{ $don->user->email }}">{{ $don->user->email }}</a></dd>
          <dt>Type d'article</dt><dd>{{ $don->typeLabel() }}</dd>
          <dt>Quantité</dt><dd>{{ $don->quantite }} article(s)</dd>
          <dt>Poids estimé</dt><dd>{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</dd>
          <dt>État général</dt><dd>{{ $don->etatLabel() }}</dd>
          <dt>Mode de remise</dt><dd>{{ $don->modeLabel() }}</dd>
          @if ($don->adresse_collecte)
            <dt>Adresse de collecte</dt><dd>{{ $don->adresse_collecte }}</dd>
          @endif
          <dt>Date de remise</dt><dd>{{ $don->date_remise->format('d/m/Y') }}</dd>
          <dt>Message</dt><dd class="tcd-pre">{{ $don->message ?: '—' }}</dd>
          <dt>Proposé le</dt><dd>{{ $don->created_at->format('d/m/Y à H:i') }}</dd>
        </dl>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  <div class="v-col-md-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Changer le statut</div></div></div>
      <div class="v-card-text">
        <form method="POST" action="{{ route('admin.dons.dons.statut', $don) }}">
          @csrf
          @method('PATCH')
          <div class="tcd-field">
            <label for="statut" class="tcd-label">Nouveau statut</label>
            <select id="statut" name="statut" @class(['tcd-input', 'is-invalid' => $errors->has('statut')])>
              @foreach (\App\Models\Don::STATUTS as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected(old('statut', $don->statut) === $optValue)>{{ $optLabel }}</option>
              @endforeach
            </select>
            @error('statut') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          @include('admin.dons.partials.btn', ['label' => 'Mettre à jour', 'icon' => 'bx-check'])
        </form>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
