{{-- Campaign form (create / edit). Paramètres : campagne, associations, action, method --}}
@php($cibles = old('type_articles_cibles', $campagne->type_articles_cibles ?? []))

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
  @csrf
  @if (($method ?? 'POST') !== 'POST')
    @method($method)
  @endif

  <div class="v-row">
    <div class="v-col-md-8 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Campagne</div></div></div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="association_id" class="tcd-label">Association <span class="tcd-required">*</span></label>
            <select id="association_id" name="association_id" @class(['tcd-input', 'is-invalid' => $errors->has('association_id')]) required>
              <option value="">— Choisir —</option>
              @foreach ($associations as $id => $nom)
                <option value="{{ $id }}" @selected((string) old('association_id', $campagne->association_id) === (string) $id)>{{ $nom }}</option>
              @endforeach
            </select>
            @error('association_id') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field">
            <label for="titre" class="tcd-label">Titre <span class="tcd-required">*</span></label>
            <input type="text" id="titre" name="titre" maxlength="150" value="{{ old('titre', $campagne->titre) }}" @class(['tcd-input', 'is-invalid' => $errors->has('titre')]) required>
            @error('titre') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field">
            <div class="d-flex justify-space-between align-center gap-2 mb-1">
              <label for="description" class="tcd-label mb-0">Description <span class="tcd-required">*</span></label>
              @include('admin.dons.partials.btn', ['type' => 'button', 'label' => 'Générer avec l\'IA', 'icon' => 'bx-bulb', 'variant' => 'tonal', 'size' => 'small',
                'attributs' => ['data-ia-rediger' => '', 'data-type' => 'campagne', 'data-cible' => 'description',
                  'data-champs' => json_encode(['titre' => '#titre', 'objectif_kg' => '#objectif_kg', 'association_id' => '#association_id', 'type_articles_cibles' => 'input[name="type_articles_cibles[]"]'])]])
            </div>
            <textarea id="description" name="description" rows="5" @class(['tcd-input', 'is-invalid' => $errors->has('description')]) required>{{ old('description', $campagne->description) }}</textarea>
            <div data-ia-message="description"></div>
            @error('description') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field mb-0">
            <span class="tcd-label">Types d'articles ciblés</span>
            <div class="d-flex flex-wrap gap-4">
              @foreach (\App\Enums\Dons\TypeArticle::options() as $valeur => $libelle)
                <label class="tcd-check">
                  <input type="checkbox" name="type_articles_cibles[]" value="{{ $valeur }}" @checked(in_array($valeur, $cibles, true))> {{ $libelle }}
                </label>
              @endforeach
            </div>
            <div class="tcd-hint">Aucun coché = tous les textiles sont acceptés.</div>
            @error('type_articles_cibles') <div class="tcd-error">{{ $message }}</div> @enderror
            @error('type_articles_cibles.*') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-md-4 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Objectif &amp; période</div></div></div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="objectif_kg" class="tcd-label">Objectif (kg) <span class="tcd-required">*</span></label>
            <input type="number" id="objectif_kg" name="objectif_kg" min="10" step="1" value="{{ old('objectif_kg', $campagne->objectif_kg) }}" @class(['tcd-input', 'is-invalid' => $errors->has('objectif_kg')]) required>
            @error('objectif_kg') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field">
            <label for="date_debut" class="tcd-label">Début <span class="tcd-required">*</span></label>
            <input type="date" id="date_debut" name="date_debut" value="{{ old('date_debut', $campagne->date_debut?->format('Y-m-d')) }}" @class(['tcd-input', 'is-invalid' => $errors->has('date_debut')]) required>
            @error('date_debut') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field">
            <label for="date_fin" class="tcd-label">Fin <span class="tcd-required">*</span></label>
            <input type="date" id="date_fin" name="date_fin" value="{{ old('date_fin', $campagne->date_fin?->format('Y-m-d')) }}" @class(['tcd-input', 'is-invalid' => $errors->has('date_fin')]) required>
            @error('date_fin') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field mb-0">
            <label for="statut" class="tcd-label">Statut <span class="tcd-required">*</span></label>
            <select id="statut" name="statut" @class(['tcd-input', 'is-invalid' => $errors->has('statut')]) required>
              @foreach (\App\Enums\Dons\StatutCampagne::options() as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('statut', $campagne->statut?->value) === $valeur)>{{ $libelle }}</option>
              @endforeach
            </select>
            <div class="tcd-hint">Seules les campagnes actives sont visibles sur le site. Elles passent en « Terminée » la nuit qui suit leur date de fin.</div>
            @error('statut') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>

      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Image</div></div></div>
        <div class="v-card-text">
          @if ($campagne->image_url)
            <div class="d-flex align-center gap-3 mb-3">
              <img src="{{ $campagne->image_url }}" alt="Image actuelle" class="tcd-logo tcd-logo-lg">
              <label class="tcd-check text-body-2">
                <input type="checkbox" name="supprimer_image" value="1" @checked(old('supprimer_image'))> Supprimer l'image
              </label>
            </div>
          @endif
          <div class="tcd-field mb-0">
            <label for="image" class="tcd-label">{{ $campagne->image ? 'Remplacer l\'image' : 'Image' }}</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" @class(['tcd-input', 'is-invalid' => $errors->has('image')])>
            <div class="tcd-hint">JPG, PNG ou WebP, 2 Mo maximum.</div>
            @error('image') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-12 d-flex gap-3">
      @include('admin.dons.partials.btn', ['label' => $campagne->exists ? 'Enregistrer les modifications' : 'Créer la campagne', 'icon' => 'bx-save'])
      @include('admin.dons.partials.btn', ['href' => $campagne->exists ? route('admin.dons.campagnes.show', $campagne) : route('admin.dons.campagnes.index'), 'label' => 'Annuler', 'variant' => 'tonal', 'color' => 'secondary'])
    </div>
  </div>
</form>

@include('dons.partials.ia-rediger')
