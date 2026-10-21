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

        <form method="POST" action="{{ route('dons.store', $association) }}" novalidate id="formulaire-don">
          @csrf
          <input type="hidden" name="ia_jeton" id="ia_jeton" value="{{ old('ia_jeton') }}">

          <div class="card border-0 bg-light mb-4">
            <div class="card-body">
              <label for="photo-ia" class="form-label fw-semibold mb-1">Photo de vos textiles <span class="fw-normal text-muted">(facultatif)</span></label>
              <p class="small text-muted mb-2">L'IA analyse la photo et pré-remplit le formulaire. Vous pourrez tout modifier. La photo n'est pas conservée.</p>
              {{-- Sans attribut name : la photo est envoyée seulement à l'analyse, pas avec le don. --}}
              <input type="file" id="photo-ia" accept="image/jpeg,image/png,image/webp" @class(['form-control', 'is-invalid' => $errors->has('photo')])
                data-url="{{ route('dons.ia.analyser-photo') }}">
              @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div id="ia-photo-statut" class="small mt-2" role="status" aria-live="polite"></div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="type_article" class="form-label">Type d'article <span class="text-danger">*</span> @include('dons.partials.badge-ia', ['champ' => 'type_article'])</label>
              <select id="type_article" name="type_article" @class(['form-select', 'is-invalid' => $errors->has('type_article')]) required>
                <option value="">— Choisir —</option>
                @foreach (\App\Enums\Dons\TypeArticle::options() as $value => $label)
                  <option value="{{ $value }}" @selected(old('type_article', $typeChoisi) === $value)>{{ $label }}</option>
                @endforeach
              </select>
              @error('type_article') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="etat_general" class="form-label">État général <span class="text-danger">*</span> @include('dons.partials.badge-ia', ['champ' => 'etat_general'])</label>
              <select id="etat_general" name="etat_general" @class(['form-select', 'is-invalid' => $errors->has('etat_general')]) required>
                <option value="">— Choisir —</option>
                @foreach (\App\Enums\Dons\EtatGeneral::options() as $value => $label)
                  <option value="{{ $value }}" @selected(old('etat_general') === $value)>{{ $label }}</option>
                @endforeach
              </select>
              @error('etat_general') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="quantite" class="form-label">Quantité (nombre d'articles) <span class="text-danger">*</span> @include('dons.partials.badge-ia', ['champ' => 'quantite'])</label>
              <input type="number" id="quantite" name="quantite" min="1" max="500" step="1" value="{{ old('quantite') }}" @class(['form-control', 'is-invalid' => $errors->has('quantite')]) required>
              @error('quantite') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="poids_kg" class="form-label">Poids estimé (kg) @include('dons.partials.badge-ia', ['champ' => 'poids_kg'])</label>
              <input type="number" id="poids_kg" name="poids_kg" min="0.1" step="0.01" value="{{ old('poids_kg') }}" @class(['form-control', 'is-invalid' => $errors->has('poids_kg')])>
              @error('poids_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
              <span class="form-label d-block">Mode de remise <span class="text-danger">*</span></span>
              @foreach (\App\Enums\Dons\ModeRemise::options() as $value => $label)
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

            <div class="col-12" data-collecte="oui" @if ($mode !== 'collecte_a_domicile') hidden @endif>
              <label for="creneau_id" class="form-label">Créneau de collecte <span class="text-danger">*</span></label>
              @if ($creneaux->isEmpty())
                <div class="alert alert-warning mb-0">Aucun créneau de collecte n'est ouvert pour le moment : choisissez le dépôt sur place, ou revenez plus tard.</div>
              @else
                <select id="creneau_id" name="creneau_id" @class(['form-select', 'is-invalid' => $errors->has('creneau_id')])>
                  <option value="">— Choisir un créneau —</option>
                  @foreach ($creneaux as $creneau)
                    <option value="{{ $creneau->id }}" @selected((string) old('creneau_id') === (string) $creneau->id)>
                      {{ $creneau->libelle() }} — {{ $creneau->placesRestantes() }} place(s)
                    </option>
                  @endforeach
                </select>
                <div class="form-text">L'association passera chez vous pendant ce créneau.</div>
              @endif
              @error('creneau_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6" data-collecte="non" @if ($mode === 'collecte_a_domicile') hidden @endif>
              <label for="date_remise" class="form-label">Date de remise souhaitée <span class="text-danger">*</span></label>
              <input type="date" id="date_remise" name="date_remise" min="{{ now()->toDateString() }}" value="{{ old('date_remise') }}" @class(['form-control', 'is-invalid' => $errors->has('date_remise')]) required>
              @error('date_remise') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            @if ($campagnes->isNotEmpty())
              <div class="col-md-6">
                <label for="campagne_id" class="form-label">Campagne de collecte</label>
                <select id="campagne_id" name="campagne_id" @class(['form-select', 'is-invalid' => $errors->has('campagne_id')])>
                  <option value="">— Aucune campagne —</option>
                  @foreach ($campagnes as $campagne)
                    <option value="{{ $campagne->id }}" @selected((string) old('campagne_id', $campagneChoisie) === (string) $campagne->id)>
                      {{ $campagne->titre }} (jusqu'au {{ $campagne->date_fin->format('d/m') }})
                    </option>
                  @endforeach
                </select>
                @error('campagne_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            @endif

            <div class="col-12">
              <label for="message" class="form-label">Message pour l'association @include('dons.partials.badge-ia', ['champ' => 'message'])</label>
              <textarea id="message" name="message" rows="4" @class(['form-control', 'is-invalid' => $errors->has('message')]) placeholder="Tailles, précisions, disponibilités…">{{ old('message') }}</textarea>
              @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-dark rounded-pill px-5" id="envoyer-don">Proposer mon don</button>
              <a href="{{ route('dons.associations.show', $association) }}" class="btn btn-link">Annuler</a>
            </div>
          </div>
        </form>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 bg-light mb-4">
          <div class="card-body">
            <h5 class="text-uppercase mb-3">Ce dont nous avons besoin</h5>
            <p class="mb-0" style="white-space: pre-line">{{ $association->besoins }}</p>
          </div>
        </div>

        {{-- IA 2 : associations recommandées selon le type d'article, la ville du donateur et la description --}}
        <div class="card border-0 shadow-sm" id="ia-recommandations" data-url="{{ route('dons.ia.recommandations') }}" data-association="{{ $association->id }}">
          <div class="card-body">
            <h5 class="text-uppercase mb-2">Associations recommandées</h5>
            <label for="ville_donateur" class="form-label small mb-1">Votre ville</label>
            <select id="ville_donateur" class="form-select form-select-sm mb-3">
              <option value="">— Choisir —</option>
              @foreach ($villes as $v)
                <option value="{{ $v }}" @selected($v === $association->ville)>{{ $v }}</option>
              @endforeach
            </select>
            <p class="small text-muted mb-0" data-ia-reco-etat>Choisissez le type d'article pour voir les associations qui en ont le plus besoin.</p>
            <div class="d-flex flex-column gap-2 mt-2" data-ia-reco-liste></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@include('dons.partials.collecte-toggle')
@include('dons.partials.ia-formulaire-don')
@include('dons.partials.assistant')
@endsection
