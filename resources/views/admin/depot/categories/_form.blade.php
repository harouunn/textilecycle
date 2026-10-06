{{-- Formulaire partagé création / modification d'une catégorie --}}
<div class="v-row">
  <div class="v-col-md-6 v-col-12">
    <label for="nom" class="tc-label">Nom *</label>
    <input id="nom" type="text" name="nom" value="{{ old('nom', $categorie->nom) }}" required maxlength="100"
      @class(['tc-control', 'is-invalid' => $errors->has('nom')])>
    @error('nom')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-6 v-col-12">
    <label for="icone" class="tc-label">Icône (classe Boxicons)</label>
    <input id="icone" type="text" name="icone" value="{{ old('icone', $categorie->icone) }}" maxlength="50" placeholder="ex. bx-closet"
      @class(['tc-control', 'is-invalid' => $errors->has('icone')])>
    @error('icone')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-12">
    <label for="description" class="tc-label">Description</label>
    <textarea id="description" name="description" maxlength="1000"
      @class(['tc-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $categorie->description) }}</textarea>
    @error('description')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>
</div>

<div class="d-flex gap-4 mt-6">
  @include('admin.depot.partials.btn', ['label' => 'Enregistrer', 'icon' => 'bx-check'])
  @include('admin.depot.partials.btn', ['label' => 'Annuler', 'variant' => 'outlined', 'color' => 'secondary', 'href' => route('admin.depot.categories.index')])
</div>
