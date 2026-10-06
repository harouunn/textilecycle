@php
    $formErrors = $errors->getBag('projet');
    $formOld = fn ($field, $default = null) => $formErrors->any() ? old($field, $default) : $default;
@endphp
<p class="tcu-help">* Champs obligatoires</p>
{{-- Champs du projet (création et modification). Variables : $projet, $difficultes, $statuts --}}
<div class="v-row">
  <div class="v-col-md-8 v-col-12">
    <label for="titre" class="tcu-label">Titre <span class="text-error">*</span></label>
    <input id="titre" type="text" name="titre" value="{{ $formOld('titre', $projet->titre) }}" minlength="5" maxlength="150" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('titre')]) aria-invalid="{{ $formErrors->has('titre') ? 'true' : 'false' }}">
    @error('titre', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-md-4 v-col-12">
    <label for="statut" class="tcu-label">Statut <span class="text-error">*</span></label>
    <select id="statut" name="statut" required @class(['tcu-input', 'is-invalid' => $formErrors->has('statut')]) aria-invalid="{{ $formErrors->has('statut') ? 'true' : 'false' }}">
      @foreach ($statuts as $valeur => $libelle)
        <option value="{{ $valeur }}" @selected($formOld('statut', $projet->statut?->value ?? 'brouillon') === $valeur)>{{ $libelle }}</option>
      @endforeach
    </select>
    @error('statut', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-md-6 v-col-12">
    <label for="vetement_origine" class="tcu-label">Vêtement d'origine <span class="text-error">*</span></label>
    <input id="vetement_origine" type="text" name="vetement_origine" value="{{ $formOld('vetement_origine', $projet->vetement_origine) }}" minlength="3" maxlength="255" placeholder="ex. vieux jean" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('vetement_origine')]) aria-invalid="{{ $formErrors->has('vetement_origine') ? 'true' : 'false' }}">
    @error('vetement_origine', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-md-6 v-col-12">
    <label for="resultat" class="tcu-label">Résultat <span class="text-error">*</span></label>
    <input id="resultat" type="text" name="resultat" value="{{ $formOld('resultat', $projet->resultat) }}" minlength="3" maxlength="255" placeholder="ex. sac cabas" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('resultat')]) aria-invalid="{{ $formErrors->has('resultat') ? 'true' : 'false' }}">
    @error('resultat', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-md-6 v-col-12">
    <label for="difficulte" class="tcu-label">Difficulté <span class="text-error">*</span></label>
    <select id="difficulte" name="difficulte" required @class(['tcu-input', 'is-invalid' => $formErrors->has('difficulte')]) aria-invalid="{{ $formErrors->has('difficulte') ? 'true' : 'false' }}">
      <option value="">— Choisir —</option>
      @foreach ($difficultes as $valeur => $libelle)
        <option value="{{ $valeur }}" @selected($formOld('difficulte', $projet->difficulte?->value) === $valeur)>{{ $libelle }}</option>
      @endforeach
    </select>
    @error('difficulte', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-md-6 v-col-12">
    <label for="duree_minutes" class="tcu-label">Durée (minutes) <span class="text-error">*</span></label>
    <input id="duree_minutes" type="number" name="duree_minutes" value="{{ $formOld('duree_minutes', $projet->duree_minutes) }}" min="5" max="1440" step="1" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('duree_minutes')]) aria-invalid="{{ $formErrors->has('duree_minutes') ? 'true' : 'false' }}">
    <div class="tcu-help">Entre 5 et 1440 minutes (24 h).</div>
    @error('duree_minutes', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-12">
    <label for="description" class="tcu-label">Description <span class="text-error">*</span></label>
    <textarea id="description" name="description" rows="5" minlength="20" maxlength="5000" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('description')]) aria-invalid="{{ $formErrors->has('description') ? 'true' : 'false' }}">{{ $formOld('description', $projet->description) }}</textarea>
    @error('description', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-12">
    <label for="materiel_necessaire" class="tcu-label">Matériel nécessaire <span class="text-error">*</span></label>
    <textarea id="materiel_necessaire" name="materiel_necessaire" rows="4" minlength="3" maxlength="2000" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('materiel_necessaire')]) aria-invalid="{{ $formErrors->has('materiel_necessaire') ? 'true' : 'false' }}">{{ $formOld('materiel_necessaire', $projet->materiel_necessaire) }}</textarea>
    <div class="tcu-help">Un élément par ligne.</div>
    @error('materiel_necessaire', 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  @foreach (['photo_avant' => ['Photo « avant »', $projet->photoAvantUrl()], 'photo_apres' => ['Photo « après »', $projet->photoApresUrl()]] as $champ => [$libelle, $url])
    <div class="v-col-md-6 v-col-12">
      <label for="{{ $champ }}" class="tcu-label">{{ $libelle }} *</label>
      @if ($url)
        <img src="{{ $url }}" alt="{{ $libelle }} actuelle" class="tcu-thumb mb-2">
      @endif
      <input id="{{ $champ }}" type="file" name="{{ $champ }}" accept="image/jpeg,image/png,image/webp" @required(! \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($projet, $champ))
        @class(['tcu-input', 'is-invalid' => $formErrors->has($champ)]) aria-invalid="{{ $formErrors->has($champ) ? 'true' : 'false' }}">
      <div class="tcu-help">JPG, PNG ou WEBP, 2 Mo maximum. Après une erreur, sélectionnez à nouveau les fichiers à envoyer.{{ \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($projet, $champ) ? ' Sans remplacement, la photo actuelle est conservée.' : ' Sélectionnez une image.' }}</div>
      @error($champ, 'projet') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
    </div>
  @endforeach
</div>

@include('upcycling.partials.validation-browser')
