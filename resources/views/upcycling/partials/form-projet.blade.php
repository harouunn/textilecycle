@php
    $formErrors = $errors->getBag('projet');
    $formOld = fn ($field, $default = null) => $formErrors->any() ? old($field, $default) : $default;
@endphp
<p class="form-text">* Champs obligatoires</p>
{{-- Champs du projet (front office). Variables : $projet, $difficultes --}}
<div class="row g-3">
  <div class="col-12">
    <label for="titre" class="form-label">Titre *</label>
    <input id="titre" type="text" name="titre" value="{{ $formOld('titre', $projet->titre) }}" minlength="5" maxlength="150" required
      @class(['form-control', 'is-invalid' => $formErrors->has('titre')]) aria-invalid="{{ $formErrors->has('titre') ? 'true' : 'false' }}">
    @error('titre', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="vetement_origine" class="form-label">Vêtement d'origine *</label>
    <input id="vetement_origine" type="text" name="vetement_origine" value="{{ $formOld('vetement_origine', $projet->vetement_origine) }}" minlength="3" maxlength="255" placeholder="ex. vieux jean" required
      @class(['form-control', 'is-invalid' => $formErrors->has('vetement_origine')]) aria-invalid="{{ $formErrors->has('vetement_origine') ? 'true' : 'false' }}">
    @error('vetement_origine', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="resultat" class="form-label">Résultat *</label>
    <input id="resultat" type="text" name="resultat" value="{{ $formOld('resultat', $projet->resultat) }}" minlength="3" maxlength="255" placeholder="ex. sac cabas" required
      @class(['form-control', 'is-invalid' => $formErrors->has('resultat')]) aria-invalid="{{ $formErrors->has('resultat') ? 'true' : 'false' }}">
    @error('resultat', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="difficulte" class="form-label">Difficulté *</label>
    <select id="difficulte" name="difficulte" required @class(['form-select', 'is-invalid' => $formErrors->has('difficulte')]) aria-invalid="{{ $formErrors->has('difficulte') ? 'true' : 'false' }}">
      <option value="">— Choisir —</option>
      @foreach ($difficultes as $valeur => $libelle)
        <option value="{{ $valeur }}" @selected($formOld('difficulte', $projet->difficulte?->value) === $valeur)>{{ $libelle }}</option>
      @endforeach
    </select>
    @error('difficulte', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="duree_minutes" class="form-label">Durée (minutes) *</label>
    <input id="duree_minutes" type="number" name="duree_minutes" value="{{ $formOld('duree_minutes', $projet->duree_minutes) }}" min="5" max="1440" step="1" required
      @class(['form-control', 'is-invalid' => $formErrors->has('duree_minutes')]) aria-invalid="{{ $formErrors->has('duree_minutes') ? 'true' : 'false' }}">
    <div class="form-text">Entre 5 et 1440 minutes (24 h).</div>
    @error('duree_minutes', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-12">
    <label for="description" class="form-label">Description *</label>
    <textarea id="description" name="description" rows="5" minlength="20" maxlength="5000" required
      @class(['form-control', 'is-invalid' => $formErrors->has('description')]) aria-invalid="{{ $formErrors->has('description') ? 'true' : 'false' }}">{{ $formOld('description', $projet->description) }}</textarea>
    @error('description', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-12">
    <label for="materiel_necessaire" class="form-label">Matériel nécessaire *</label>
    <textarea id="materiel_necessaire" name="materiel_necessaire" rows="4" minlength="3" maxlength="2000" required
      @class(['form-control', 'is-invalid' => $formErrors->has('materiel_necessaire')]) aria-invalid="{{ $formErrors->has('materiel_necessaire') ? 'true' : 'false' }}">{{ $formOld('materiel_necessaire', $projet->materiel_necessaire) }}</textarea>
    <div class="form-text">Un élément par ligne.</div>
    @error('materiel_necessaire', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  @foreach (['photo_avant' => ['Photo « avant »', $projet->photoAvantUrl()], 'photo_apres' => ['Photo « après »', $projet->photoApresUrl()]] as $champ => [$libelle, $url])
    <div class="col-md-6">
      <label for="{{ $champ }}" class="form-label">{{ $libelle }} *</label>
      @if ($url)
        <div class="mb-2"><img src="{{ $url }}" alt="{{ $libelle }} actuelle" class="tcu-thumb"></div>
      @endif
      <input id="{{ $champ }}" type="file" name="{{ $champ }}" accept="image/jpeg,image/png,image/webp" @required(! \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($projet, $champ))
        @class(['form-control', 'is-invalid' => $formErrors->has($champ)]) aria-invalid="{{ $formErrors->has($champ) ? 'true' : 'false' }}">
      <div class="form-text">JPG, PNG ou WEBP, 2 Mo maximum. Après une erreur, sélectionnez à nouveau les fichiers à envoyer.{{ \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($projet, $champ) ? ' Sans remplacement, la photo actuelle est conservée.' : ' Sélectionnez une image.' }}</div>
      @error($champ, 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  @endforeach
</div>

@include('upcycling.partials.validation-browser')
