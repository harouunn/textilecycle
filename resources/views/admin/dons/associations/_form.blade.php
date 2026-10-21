{{-- Association form (create / edit). Paramètres : association, responsables, action, method --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
  @csrf
  @if (($method ?? 'POST') !== 'POST')
    @method($method)
  @endif

  <div class="v-row">
    <div class="v-col-md-8 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Informations</div></div></div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="nom" class="tcd-label">Nom <span class="tcd-required">*</span></label>
            <input type="text" id="nom" name="nom" value="{{ old('nom', $association->nom) }}" @class(['tcd-input', 'is-invalid' => $errors->has('nom')]) required maxlength="255">
            @error('nom') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field">
            <div class="d-flex justify-space-between align-center gap-2 mb-1">
              <label for="description" class="tcd-label mb-0">Description <span class="tcd-required">*</span></label>
              @include('admin.dons.partials.btn', ['type' => 'button', 'label' => 'Générer avec l\'IA', 'icon' => 'bx-bulb', 'variant' => 'tonal', 'size' => 'small',
                'attributs' => ['data-ia-rediger' => '', 'data-type' => 'description', 'data-cible' => 'description',
                  'data-fixes' => json_encode($association->exists ? ['association_id' => $association->id] : []),
                  'data-champs' => json_encode(['nom' => '#nom', 'ville' => '#ville', 'besoins' => '#besoins', 'description' => '#description'])]])
            </div>
            <textarea id="description" name="description" rows="4" @class(['tcd-input', 'is-invalid' => $errors->has('description')]) required>{{ old('description', $association->description) }}</textarea>
            <div data-ia-message="description"></div>
            @error('description') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field">
            <label for="besoins" class="tcd-label">Besoins (types de vêtements recherchés) <span class="tcd-required">*</span></label>
            <textarea id="besoins" name="besoins" rows="4" @class(['tcd-input', 'is-invalid' => $errors->has('besoins')]) required placeholder="Ex. : vêtements chauds pour enfants, chaussures, couvertures…">{{ old('besoins', $association->besoins) }}</textarea>
            <div class="tcd-hint">Un besoin par ligne : il sera affiché tel quel sur la page publique de l'association.</div>
            @error('besoins') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="v-row">
            <div class="v-col-md-8 v-col-12 py-0">
              <div class="tcd-field">
                <label for="adresse" class="tcd-label">Adresse <span class="tcd-required">*</span></label>
                <input type="text" id="adresse" name="adresse" value="{{ old('adresse', $association->adresse) }}" @class(['tcd-input', 'is-invalid' => $errors->has('adresse')]) required>
                @error('adresse') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="v-col-md-4 v-col-12 py-0">
              <div class="tcd-field">
                <label for="ville" class="tcd-label">Ville <span class="tcd-required">*</span></label>
                <input type="text" id="ville" name="ville" value="{{ old('ville', $association->ville) }}" @class(['tcd-input', 'is-invalid' => $errors->has('ville')]) required>
                @error('ville') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="latitude" class="tcd-label">Latitude</label>
                <input type="number" id="latitude" name="latitude" step="0.0000001" min="-90" max="90" value="{{ old('latitude', $association->latitude) }}" @class(['tcd-input', 'is-invalid' => $errors->has('latitude')]) placeholder="36.8065">
                @error('latitude') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="longitude" class="tcd-label">Longitude</label>
                <input type="number" id="longitude" name="longitude" step="0.0000001" min="-180" max="180" value="{{ old('longitude', $association->longitude) }}" @class(['tcd-input', 'is-invalid' => $errors->has('longitude')]) placeholder="10.1815">
                @error('longitude') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-md-4 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Contact</div></div></div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="telephone" class="tcd-label">Téléphone <span class="tcd-required">*</span></label>
            <input type="tel" id="telephone" name="telephone" value="{{ old('telephone', $association->telephone) }}" @class(['tcd-input', 'is-invalid' => $errors->has('telephone')]) required placeholder="+216 71 000 000">
            @error('telephone') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field">
            <label for="email" class="tcd-label">E-mail <span class="tcd-required">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $association->email) }}" @class(['tcd-input', 'is-invalid' => $errors->has('email')]) required>
            @error('email') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field mb-0">
            <label for="site_web" class="tcd-label">Site web</label>
            <input type="url" id="site_web" name="site_web" value="{{ old('site_web', $association->site_web) }}" @class(['tcd-input', 'is-invalid' => $errors->has('site_web')]) placeholder="https://">
            @error('site_web') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>

      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Gestion des dons</div></div></div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="responsable_id" class="tcd-label">Responsable (Espace association)</label>
            <select id="responsable_id" name="responsable_id" @class(['tcd-input', 'is-invalid' => $errors->has('responsable_id')])>
              <option value="">— Aucun —</option>
              @foreach ($responsables as $user)
                <option value="{{ $user->id }}" @selected((string) old('responsable_id', $association->responsable_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
              @endforeach
            </select>
            <div class="tcd-hint">Ce compte pourra accepter, planifier et réceptionner les dons envoyés à l'association.</div>
            @error('responsable_id') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <div class="tcd-field mb-0">
            <label for="capacite_mensuelle_kg" class="tcd-label">Capacité mensuelle (kg)</label>
            <input type="number" id="capacite_mensuelle_kg" name="capacite_mensuelle_kg" min="0" step="1" value="{{ old('capacite_mensuelle_kg', $association->capacite_mensuelle_kg) }}" @class(['tcd-input', 'is-invalid' => $errors->has('capacite_mensuelle_kg')])>
            <div class="tcd-hint">0 = non renseignée.</div>
            @error('capacite_mensuelle_kg') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>

      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Logo &amp; visibilité</div></div></div>
        <div class="v-card-text">
          @if ($association->logo_url)
            <div class="d-flex align-center gap-3 mb-3">
              <img src="{{ $association->logo_url }}" alt="Logo actuel" class="tcd-logo tcd-logo-lg">
              <label class="tcd-check text-body-2">
                <input type="checkbox" name="supprimer_logo" value="1" @checked(old('supprimer_logo'))> Supprimer le logo
              </label>
            </div>
          @endif
          <div class="tcd-field">
            <label for="logo" class="tcd-label">{{ $association->logo ? 'Remplacer le logo' : 'Logo' }}</label>
            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp" @class(['tcd-input', 'is-invalid' => $errors->has('logo')])>
            <div class="tcd-hint">JPG, PNG ou WebP, 2 Mo maximum.</div>
            @error('logo') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
          <label class="tcd-check">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" @checked(old('active', $association->active))>
            Association active (visible sur le site)
          </label>
          @error('active') <div class="tcd-error">{{ $message }}</div> @enderror
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-12 d-flex gap-3">
      @include('admin.dons.partials.btn', ['label' => $association->exists ? 'Enregistrer les modifications' : 'Créer l\'association', 'icon' => 'bx-save'])
      @include('admin.dons.partials.btn', ['href' => $association->exists ? route('admin.dons.associations.show', $association) : route('admin.dons.associations.index'), 'label' => 'Annuler', 'variant' => 'tonal', 'color' => 'secondary'])
    </div>
  </div>
</form>

@include('dons.partials.ia-rediger')
