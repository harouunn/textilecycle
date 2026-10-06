{{-- Formulaire « Déposer un vêtement » / modification d'un de mes dépôts --}}
@php
  $selects = [
      'taille' => ['label' => 'Taille', 'options' => \App\Enums\Depot\Taille::options()],
      'genre' => ['label' => 'Genre', 'options' => \App\Enums\Depot\Genre::options()],
      'etat' => ['label' => 'État', 'options' => \App\Enums\Depot\Etat::options()],
  ];
@endphp

<div class="row g-4 tc-depot-form">
  <div class="col-md-8">
    <label for="titre" class="form-label">Titre *</label>
    <input id="titre" type="text" name="titre" value="{{ old('titre', $vetement->titre) }}" required maxlength="150" placeholder="ex. Chemise en lin bleue"
      @class(['form-control form-control-lg', 'is-invalid' => $errors->has('titre')])>
    @error('titre')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label for="categorie_id" class="form-label">Catégorie *</label>
    <select id="categorie_id" name="categorie_id" required @class(['form-select form-select-lg', 'is-invalid' => $errors->has('categorie_id')])>
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
    <textarea id="description" name="description" rows="4" required maxlength="2000" placeholder="Décrivez le vêtement : couleur, coupe, défauts éventuels…"
      @class(['form-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $vetement->description) }}</textarea>
    @error('description')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  @foreach ($selects as $name => $select)
    <div class="col-md-4">
      <label for="{{ $name }}" class="form-label">{{ $select['label'] }} *</label>
      <select id="{{ $name }}" name="{{ $name }}" required @class(['form-select form-select-lg', 'is-invalid' => $errors->has($name)])>
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
      @class(['form-control form-control-lg', 'is-invalid' => $errors->has('matiere')])>
    @error('matiere')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label for="date_depot" class="form-label">Date de dépôt *</label>
    <input id="date_depot" type="date" name="date_depot" value="{{ old('date_depot', $vetement->date_depot?->format('Y-m-d')) }}" required max="{{ today()->format('Y-m-d') }}"
      @class(['form-control form-control-lg', 'is-invalid' => $errors->has('date_depot')])>
    @error('date_depot')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12">
    <label for="photo" class="form-label">Photo (JPG, PNG ou WEBP, 2 Mo max.)</label>
    @if ($vetement->photo)
      <div class="d-flex align-items-center gap-3 mb-2">
        <img src="{{ $vetement->photo_url }}" alt="Photo actuelle" class="tc-thumb">
        <small>Laissez vide pour conserver la photo actuelle.</small>
      </div>
    @elseif (! $vetement->exists && session(\App\Models\Vetement::SESSION_PHOTO_ANALYSEE))
      <div class="d-flex align-items-center gap-3 mb-2">
        <img src="{{ asset('storage/'.session(\App\Models\Vetement::SESSION_PHOTO_ANALYSEE)) }}" alt="Photo analysée" class="tc-thumb">
        <small>Photo analysée : elle sera utilisée si vous n'en choisissez pas une autre.</small>
      </div>
    @endif
    <div class="d-flex flex-wrap gap-2">
      <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
        @class(['form-control flex-grow-1 w-auto', 'is-invalid' => $errors->has('photo')])>
      @isset($analyseRoute)
        <button type="submit" formaction="{{ route($analyseRoute) }}" formnovalidate class="btn btn-outline-dark text-uppercase">Analyser la photo</button>
      @endisset
    </div>
    @isset($analyseRoute)
      <div class="form-text">Indiquez le titre (ex. « Chemise en lin bleue, taille M »), choisissez la photo puis cliquez sur « Analyser » : catégorie, matière, état et description sont proposés automatiquement.</div>
    @endisset
    @error('photo')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12 d-flex flex-wrap gap-3 mt-4">
    <button type="submit" class="btn btn-dark btn-lg text-uppercase">{{ $submitLabel }}</button>
    <a href="{{ route('depot.mes-depots.index') }}" class="btn btn-outline-dark btn-lg text-uppercase">Annuler</a>
  </div>
</div>
