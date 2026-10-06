@extends('layouts.front')

@section('title', 'Faire un don à '.$association->nom)

@section('content')
@php($mode = old('mode_remise', 'depot_sur_place'))

<section class="bg-light py-5">
  <div class="container">
    <a href="{{ route('dons.associations.show', $association) }}" class="small text-uppercase">‹ {{ $association->nom }}</a>
    <h1 class="section-title mt-3 mb-1">Faire un don</h1>
    <p class="mb-0 text-muted">à {{ $association->nom }} ({{ $association->ville }})</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-8">
        @include('dons.partials.flash')

        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            Le formulaire contient {{ $errors->count() }} erreur(s). Veuillez corriger les champs signalés.
          </div>
        @endif

        <form method="POST" action="{{ route('dons.store', $association) }}" novalidate>
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label for="type_article" class="form-label">Type d'article <span class="text-danger">*</span></label>
              <select id="type_article" name="type_article" @class(['form-select', 'is-invalid' => $errors->has('type_article')]) required>
                <option value="">— Choisir —</option>
                @foreach (\App\Models\Don::TYPES as $value => $label)
                  <option value="{{ $value }}" @selected(old('type_article') === $value)>{{ $label }}</option>
                @endforeach
              </select>
              @error('type_article') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="etat_general" class="form-label">État général <span class="text-danger">*</span></label>
              <select id="etat_general" name="etat_general" @class(['form-select', 'is-invalid' => $errors->has('etat_general')]) required>
                <option value="">— Choisir —</option>
                @foreach (\App\Models\Don::ETATS as $value => $label)
                  <option value="{{ $value }}" @selected(old('etat_general') === $value)>{{ $label }}</option>
                @endforeach
              </select>
              @error('etat_general') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="quantite" class="form-label">Quantité (nombre d'articles) <span class="text-danger">*</span></label>
              <input type="number" id="quantite" name="quantite" min="1" max="500" step="1" value="{{ old('quantite') }}" @class(['form-control', 'is-invalid' => $errors->has('quantite')]) required>
              @error('quantite') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="poids_kg" class="form-label">Poids estimé (kg)</label>
              <input type="number" id="poids_kg" name="poids_kg" min="0.1" step="0.01" value="{{ old('poids_kg') }}" @class(['form-control', 'is-invalid' => $errors->has('poids_kg')])>
              @error('poids_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
              <span class="form-label d-block">Mode de remise <span class="text-danger">*</span></span>
              @foreach (\App\Models\Don::MODES as $value => $label)
                <div class="form-check form-check-inline">
                  <input type="radio" id="mode_{{ $value }}" name="mode_remise" value="{{ $value }}" data-toggle-collecte
                    @class(['form-check-input', 'is-invalid' => $errors->has('mode_remise')]) @checked($mode === $value)>
                  <label for="mode_{{ $value }}" class="form-check-label">{{ $label }}</label>
                </div>
              @endforeach
              @error('mode_remise') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="col-12" id="adresse-collecte-group" @if ($mode !== 'collecte_a_domicile') hidden @endif>
              <label for="adresse_collecte" class="form-label">Adresse de collecte <span class="text-danger">*</span></label>
              <input type="text" id="adresse_collecte" name="adresse_collecte" value="{{ old('adresse_collecte') }}" @class(['form-control', 'is-invalid' => $errors->has('adresse_collecte')]) placeholder="N°, rue, ville">
              @error('adresse_collecte') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="date_remise" class="form-label">Date de remise souhaitée <span class="text-danger">*</span></label>
              <input type="date" id="date_remise" name="date_remise" min="{{ now()->toDateString() }}" value="{{ old('date_remise') }}" @class(['form-control', 'is-invalid' => $errors->has('date_remise')]) required>
              @error('date_remise') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
              <label for="message" class="form-label">Message pour l'association</label>
              <textarea id="message" name="message" rows="4" @class(['form-control', 'is-invalid' => $errors->has('message')]) placeholder="Tailles, précisions, disponibilités…">{{ old('message') }}</textarea>
              @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-dark rounded-pill px-5">Proposer mon don</button>
              <a href="{{ route('dons.associations.show', $association) }}" class="btn btn-link">Annuler</a>
            </div>
          </div>
        </form>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h5 class="text-uppercase mb-3">Ce dont nous avons besoin</h5>
            <p class="mb-0" style="white-space: pre-line">{{ $association->besoins }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@include('dons.partials.collecte-toggle')
@endsection
