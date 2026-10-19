{{-- Formulaire partagé création / modification d'une catégorie --}}
<div class="v-row">
  <div class="v-col-md-6 v-col-12">
    <label for="nom" class="tc-label">Nom *</label>
    <input id="nom" type="text" name="nom" value="{{ old('nom', $categorie->nom) }}" required maxlength="100"
      @class(['tc-control', 'is-invalid' => $errors->has('nom')])>
    @if ($categorie->slug)
      <div class="text-body-2 text-disabled mt-1">Adresse : /{{ $categorie->slug }} (mise à jour automatiquement avec le nom)</div>
    @endif
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
    <div class="text-body-2 text-disabled mt-1">Affichée en tête du catalogue quand la catégorie est sélectionnée.</div>
    @error('description')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-4 v-col-12">
    <label for="couleur" class="tc-label">Couleur du badge *</label>
    <input id="couleur" type="color" name="couleur" value="{{ old('couleur', $categorie->couleur) }}" required
      @class(['tc-control', 'is-invalid' => $errors->has('couleur')])>
    @error('couleur')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-4 v-col-12">
    <label for="ordre" class="tc-label">Ordre d'affichage *</label>
    <input id="ordre" type="number" name="ordre" value="{{ old('ordre', $categorie->ordre) }}" required min="0" max="999" step="1"
      @class(['tc-control', 'is-invalid' => $errors->has('ordre')])>
    <div class="text-body-2 text-disabled mt-1">Les plus petits numéros apparaissent en premier.</div>
    @error('ordre')
      <div class="tc-error">{{ $message }}</div>
    @enderror
  </div>

  <div class="v-col-md-4 v-col-12 d-flex align-center">
    <label class="tc-switch mt-md-4">
      <input type="checkbox" name="active" value="1" @checked(old('active', $categorie->active))>
      <span>
        <span class="d-block">Catégorie active</span>
        <span class="text-body-2 text-disabled">Visible dans le catalogue et le formulaire de dépôt</span>
      </span>
    </label>
  </div>
</div>

<div class="d-flex gap-4 mt-6">
  @include('admin.depot.partials.btn', ['label' => 'Enregistrer', 'icon' => 'bx-check'])
  @include('admin.depot.partials.btn', ['label' => 'Annuler', 'variant' => 'outlined', 'color' => 'secondary', 'href' => route('admin.depot.categories.index')])
</div>
