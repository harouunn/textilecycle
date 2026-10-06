@php
    $formErrors = $errors->getBag('etape');
    $formOld = fn ($field, $default = null) => $formErrors->any() ? old($field, $default) : $default;
@endphp
<p class="tcu-help">* Champs obligatoires</p>
{{-- Champs d'une étape. Variables : $etape (EtapeProjet ou null), $numeroParDefaut (int) --}}
<div class="v-row">
  <div class="v-col-md-3 v-col-12">
    <label for="numero" class="tcu-label">Numéro <span class="text-error">*</span></label>
    <input id="numero" type="number" name="numero" value="{{ $formOld('numero', $etape?->numero ?? $numeroParDefaut) }}" min="1" max="100" step="1" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('numero')]) aria-invalid="{{ $formErrors->has('numero') ? 'true' : 'false' }}">
    @error('numero', 'etape') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-md-9 v-col-12">
    <label for="etape_titre" class="tcu-label">Titre de l'étape <span class="text-error">*</span></label>
    <input id="etape_titre" type="text" name="titre" value="{{ $formOld('titre', $etape?->titre) }}" minlength="3" maxlength="150" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('titre')]) aria-invalid="{{ $formErrors->has('titre') ? 'true' : 'false' }}">
    @error('titre', 'etape') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-12">
    <label for="contenu" class="tcu-label">Contenu <span class="text-error">*</span></label>
    <textarea id="contenu" name="contenu" rows="4" minlength="10" maxlength="5000" required
      @class(['tcu-input', 'is-invalid' => $formErrors->has('contenu')]) aria-invalid="{{ $formErrors->has('contenu') ? 'true' : 'false' }}">{{ $formOld('contenu', $etape?->contenu) }}</textarea>
    @error('contenu', 'etape') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>

  <div class="v-col-12">
    <label for="photo" class="tcu-label">Photo *</label>
    @if ($etape?->photoUrl())
      <img src="{{ $etape->photoUrl() }}" alt="Photo actuelle" class="tcu-thumb mb-2">
    @endif
    <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" @required(! \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($etape, 'photo'))
      @class(['tcu-input', 'is-invalid' => $formErrors->has('photo')]) aria-invalid="{{ $formErrors->has('photo') ? 'true' : 'false' }}">
    <div class="tcu-help">JPG, PNG ou WEBP, 2 Mo maximum. Après une erreur, sélectionnez à nouveau les fichiers à envoyer.{{ \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($etape, 'photo') ? ' Sans remplacement, la photo actuelle est conservée.' : ' Sélectionnez une image.' }}</div>
    @error('photo', 'etape') <div data-server-error class="tcu-error">{{ $message }}</div> @enderror
  </div>
</div>

@include('upcycling.partials.validation-browser')
