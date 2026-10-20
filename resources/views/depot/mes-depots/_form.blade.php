{{-- Formulaire « Déposer un vêtement » / modification d'un de mes dépôts, en trois étapes --}}
@php
  $selects = [
      'taille' => ['label' => 'Taille', 'options' => \App\Enums\Depot\Taille::options()],
      'genre' => ['label' => 'Genre', 'options' => \App\Enums\Depot\Genre::options()],
      'etat' => ['label' => 'État', 'options' => \App\Enums\Depot\Etat::options()],
  ];
  $photoAnalysee = ! $vetement->exists ? session(\App\Models\Vetement::SESSION_PHOTO_ANALYSEE) : null;
@endphp

<div class="tc-form">
  {{-- Étape 1 : titre et photo, avec l'analyse automatique --}}
  <div class="tc-section-form">
    <h3><span>1</span> Photo et titre</h3>

    <div class="mb-4">
      <label for="titre" class="form-label">Titre *</label>
      <input id="titre" type="text" name="titre" value="{{ old('titre', $vetement->titre) }}" required maxlength="150" placeholder="ex. Chemise en lin bleue, taille M"
        @class(['form-control', 'is-invalid' => $errors->has('titre')])>
      @error('titre')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <label for="photo" class="form-label">Photo (JPG, PNG ou WEBP, 2 Mo max.)</label>
    <div class="d-flex flex-wrap align-items-center gap-3">
      @if ($vetement->photo)
        <img src="{{ $vetement->miniature_url }}" alt="Photo actuelle" class="tc-vignette">
      @elseif ($photoAnalysee)
        <img src="{{ asset('storage/'.$photoAnalysee) }}" alt="Photo analysée" class="tc-vignette">
      @endif
      <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
        @class(['form-control w-auto flex-grow-1', 'is-invalid' => $errors->has('photo')])>
      @isset($analyseRoute)
        <button type="submit" formaction="{{ route($analyseRoute) }}" formnovalidate class="tc-btn tc-btn--clair"
          onclick="this.querySelector('span').textContent = 'Analyse par l’IA en cours…'; this.setAttribute('aria-busy', 'true')">
          <x-ui.icone nom="etincelle" /> <span>Analyser la photo</span>
        </button>
      @endisset
    </div>
    @error('photo')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <div class="form-text mt-2">
      @if ($vetement->photo)
        Laissez vide pour conserver la photo actuelle.
      @elseif ($photoAnalysee)
        Photo analysée : elle sera utilisée si vous n'en choisissez pas une autre.
      @elseif (isset($analyseRoute))
        Indiquez le titre, choisissez la photo puis « Analyser » : notre IA de vision (Ollama, sur notre serveur) regarde la photo et propose catégorie, matière, état et description.
      @endif
    </div>
  </div>

  {{-- Étape 2 : catégorie et description --}}
  <div class="tc-section-form">
    <h3><span>2</span> Catégorie et description</h3>
    <div class="row g-4">
      <div class="col-md-5">
        <label for="categorie_id" class="form-label">Catégorie *</label>
        <select id="categorie_id" name="categorie_id" required @class(['form-select', 'is-invalid' => $errors->has('categorie_id')])>
          <option value="">Choisir…</option>
          @foreach ($categories as $id => $nom)
            <option value="{{ $id }}" @selected(old('categorie_id', $vetement->categorie_id) == $id)>{{ $nom }}</option>
          @endforeach
        </select>
        @error('categorie_id')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <div class="col-12">
        <label for="description" class="form-label">Description *</label>
        <textarea id="description" name="description" rows="4" required maxlength="2000" placeholder="Couleur, coupe, défauts éventuels…"
          @class(['form-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $vetement->description) }}</textarea>
        @error('description')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>

  {{-- Étape 3 : caractéristiques --}}
  <div class="tc-section-form">
    <h3><span>3</span> Caractéristiques</h3>
    <div class="row g-4">
      @foreach ($selects as $name => $select)
        <div class="col-md-4">
          <label for="{{ $name }}" class="form-label">{{ $select['label'] }} *</label>
          <select id="{{ $name }}" name="{{ $name }}" required @class(['form-select', 'is-invalid' => $errors->has($name)])>
            <option value="">Choisir…</option>
            @foreach ($select['options'] as $value => $label)
              <option value="{{ $value }}" @selected(old($name, $vetement->{$name}?->value) === $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      @endforeach

      <div class="col-md-6">
        <label for="matiere" class="form-label">Matière *</label>
        <input id="matiere" type="text" name="matiere" value="{{ old('matiere', $vetement->matiere) }}" required maxlength="100" placeholder="ex. Coton"
          @class(['form-control', 'is-invalid' => $errors->has('matiere')])>
        @error('matiere')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="col-md-6">
        <label for="date_depot" class="form-label">Date de dépôt *</label>
        <input id="date_depot" type="date" name="date_depot" value="{{ old('date_depot', $vetement->date_depot?->format('Y-m-d')) }}" required max="{{ today()->format('Y-m-d') }}"
          @class(['form-control', 'is-invalid' => $errors->has('date_depot')])>
        @error('date_depot')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-3 mt-2">
    <button type="submit" class="tc-btn tc-btn--vert"><x-ui.icone nom="valide" /> {{ $submitLabel }}</button>
    <a href="{{ route('depot.mes-depots.index') }}" class="tc-btn tc-btn--clair">Annuler</a>
  </div>
</div>
