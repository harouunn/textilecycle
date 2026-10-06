{{-- Formulaire partagé création / modification d'un vêtement (back office) --}}
@php
  $selects = [
      'taille' => ['label' => 'Taille', 'options' => \App\Enums\Depot\Taille::options()],
      'genre' => ['label' => 'Genre', 'options' => \App\Enums\Depot\Genre::options()],
      'etat' => ['label' => 'État', 'options' => \App\Enums\Depot\Etat::options()],
      'statut' => ['label' => 'Statut', 'options' => \App\Enums\Depot\StatutVetement::options()],
  ];
@endphp

<div class="v-row">
  <div class="v-col-md-8 v-col-12">
    <label for="titre" class="tc-label">Titre *</label>
    <input id="titre" type="text" name="titre" value="{{ old('titre', $vetement->titre) }}" required maxlength="150"
      @class(['tc-control', 'is-invalid' => $errors->has('titre')])>
    @error('titre')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-4 v-col-12">
    <label for="categorie_id" class="tc-label">Catégorie *</label>
    <select id="categorie_id" name="categorie_id" required @class(['tc-control', 'is-invalid' => $errors->has('categorie_id')])>
      <option value="">— Choisir —</option>
      @foreach ($categories as $id => $nom)
        <option value="{{ $id }}" @selected(old('categorie_id', $vetement->categorie_id) == $id)>{{ $nom }}</option>
      @endforeach
    </select>
    @error('categorie_id')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-12">
    <label for="description" class="tc-label">Description *</label>
    <textarea id="description" name="description" required maxlength="2000"
      @class(['tc-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $vetement->description) }}</textarea>
    @error('description')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  @foreach ($selects as $name => $select)
    <div class="v-col-md-3 v-col-sm-6 v-col-12">
      <label for="{{ $name }}" class="tc-label">{{ $select['label'] }} *</label>
      <select id="{{ $name }}" name="{{ $name }}" required @class(['tc-control', 'is-invalid' => $errors->has($name)])>
        <option value="">— Choisir —</option>
        @foreach ($select['options'] as $value => $label)
          <option value="{{ $value }}" @selected(old($name, $vetement->{$name}?->value) === $value)>{{ $label }}</option>
        @endforeach
      </select>
      @error($name)
        <div class="tc-error">{{ $message }}</div>
      @enderror
    </div>
  @endforeach

  <div class="v-col-md-4 v-col-12">
    <label for="matiere" class="tc-label">Matière *</label>
    <input id="matiere" type="text" name="matiere" value="{{ old('matiere', $vetement->matiere) }}" required maxlength="100" placeholder="ex. Coton"
      @class(['tc-control', 'is-invalid' => $errors->has('matiere')])>
    @error('matiere')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-4 v-col-12">
    <label for="user_id" class="tc-label">Déposant *</label>
    <select id="user_id" name="user_id" required @class(['tc-control', 'is-invalid' => $errors->has('user_id')])>
      @foreach ($users as $id => $name)
        <option value="{{ $id }}" @selected(old('user_id', $vetement->user_id) == $id)>{{ $name }}</option>
      @endforeach
    </select>
    @error('user_id')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-4 v-col-12">
    <label for="date_depot" class="tc-label">Date de dépôt *</label>
    <input id="date_depot" type="date" name="date_depot" value="{{ old('date_depot', $vetement->date_depot?->format('Y-m-d')) }}" required max="{{ today()->format('Y-m-d') }}"
      @class(['tc-control', 'is-invalid' => $errors->has('date_depot')])>
    @error('date_depot')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-12">
    <label for="photo" class="tc-label">Photo (JPG, PNG ou WEBP, 2 Mo max.)</label>
    <div class="d-flex align-center gap-4">
      @if ($vetement->photo)
        <img src="{{ $vetement->photo_url }}" alt="Photo actuelle" class="tc-thumb" style="width: 64px; height: 64px;">
      @endif
      <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
        @class(['tc-control', 'is-invalid' => $errors->has('photo')])>
    </div>
    @if ($vetement->photo)
      <div class="text-body-2 text-disabled mt-1">Laissez vide pour conserver la photo actuelle.</div>
    @endif
    @error('photo')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="d-flex gap-4 mt-6">
  @include('admin.depot.partials.btn', ['label' => 'Enregistrer', 'icon' => 'bx-check'])
  @include('admin.depot.partials.btn', ['label' => 'Annuler', 'variant' => 'outlined', 'color' => 'secondary', 'href' => route('admin.depot.vetements.index')])
</div>
