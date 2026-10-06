{{-- Formulaire partagé création / modification d'une demande de réparation (back office) --}}
<div class="v-row">
  <div class="v-col-md-8 v-col-12">
    <label for="titre" class="tc-label">Titre *</label>
    <input id="titre" type="text" name="titre" value="{{ old('titre', $demande->titre) }}" required maxlength="150"
      @class(['tc-control', 'is-invalid' => $errors->has('titre')])>
    @error('titre')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-4 v-col-12">
    <label for="type_vetement" class="tc-label">Type de vêtement *</label>
    <select id="type_vetement" name="type_vetement" required @class(['tc-control', 'is-invalid' => $errors->has('type_vetement')])>
      @foreach (\App\Enums\Ateliers\TypeVetement::options() as $value => $label)
        <option value="{{ $value }}" @selected(old('type_vetement', $demande->type_vetement?->value) === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('type_vetement')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-6 v-col-12">
    <label for="atelier_id" class="tc-label">Atelier *</label>
    <select id="atelier_id" name="atelier_id" required @class(['tc-control', 'is-invalid' => $errors->has('atelier_id')])>
      <option value="">— Choisir —</option>
      @foreach ($ateliers as $id => $nom)
        <option value="{{ $id }}" @selected(old('atelier_id', $demande->atelier_id) == $id)>{{ $nom }}</option>
      @endforeach
    </select>
    @error('atelier_id')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-6 v-col-12">
    <label for="user_id" class="tc-label">Client *</label>
    <select id="user_id" name="user_id" required @class(['tc-control', 'is-invalid' => $errors->has('user_id')])>
      @foreach ($users as $id => $name)
        <option value="{{ $id }}" @selected(old('user_id', $demande->user_id) == $id)>{{ $name }}</option>
      @endforeach
    </select>
    @error('user_id')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-12">
    <label for="description" class="tc-label">Description du problème</label>
    <textarea id="description" name="description" maxlength="2000"
      @class(['tc-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $demande->description) }}</textarea>
    <div class="text-body-2 text-disabled mt-1">Description ou photo : au moins l'une des deux. Le diagnostic est recalculé à l'enregistrement.</div>
    @error('description')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-12">
    <label for="photo" class="tc-label">Photo (JPG, PNG ou WEBP, 2 Mo max.)</label>
    <div class="d-flex align-center gap-4">
      @if ($demande->photo)
        <img src="{{ $demande->photo_url }}" alt="Photo actuelle" class="tc-thumb" style="width: 64px; height: 64px;">
      @endif
      <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
        @class(['tc-control', 'is-invalid' => $errors->has('photo')])>
    </div>
    @if ($demande->photo)
      <div class="text-body-2 text-disabled mt-1">Laissez vide pour conserver la photo actuelle.</div>
    @endif
    @error('photo')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="d-flex gap-4 mt-6">
  @include('admin.depot.partials.btn', ['label' => 'Enregistrer et diagnostiquer', 'icon' => 'bx-check'])
  @include('admin.depot.partials.btn', ['label' => 'Annuler', 'variant' => 'outlined', 'color' => 'secondary', 'href' => route('admin.ateliers.demandes.index')])
</div>
