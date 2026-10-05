{{-- Donation form (create / edit). Paramètres : don, associations, users, action, method --}}
@php($mode = old('mode_remise', $don->mode_remise))

<form method="POST" action="{{ $action }}" novalidate>
  @csrf
  @if (($method ?? 'POST') !== 'POST')
    @method($method)
  @endif

  <div class="v-row">
    <div class="v-col-md-8 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Détail du don</div></div></div>
        <div class="v-card-text">
          <div class="v-row">
            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="association_id" class="tcd-label">Association <span class="tcd-required">*</span></label>
                <select id="association_id" name="association_id" @class(['tcd-input', 'is-invalid' => $errors->has('association_id')]) required>
                  <option value="">— Choisir —</option>
                  @foreach ($associations as $id => $nom)
                    <option value="{{ $id }}" @selected((string) old('association_id', $don->association_id) === (string) $id)>{{ $nom }}</option>
                  @endforeach
                </select>
                @error('association_id') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="user_id" class="tcd-label">Donateur <span class="tcd-required">*</span></label>
                <select id="user_id" name="user_id" @class(['tcd-input', 'is-invalid' => $errors->has('user_id')]) required>
                  <option value="">— Choisir —</option>
                  @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('user_id', $don->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                  @endforeach
                </select>
                @error('user_id') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="type_article" class="tcd-label">Type d'article <span class="tcd-required">*</span></label>
                <select id="type_article" name="type_article" @class(['tcd-input', 'is-invalid' => $errors->has('type_article')]) required>
                  <option value="">— Choisir —</option>
                  @foreach (\App\Models\Don::TYPES as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected(old('type_article', $don->type_article) === $optValue)>{{ $optLabel }}</option>
                  @endforeach
                </select>
                @error('type_article') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="etat_general" class="tcd-label">État général <span class="tcd-required">*</span></label>
                <select id="etat_general" name="etat_general" @class(['tcd-input', 'is-invalid' => $errors->has('etat_general')]) required>
                  <option value="">— Choisir —</option>
                  @foreach (\App\Models\Don::ETATS as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected(old('etat_general', $don->etat_general) === $optValue)>{{ $optLabel }}</option>
                  @endforeach
                </select>
                @error('etat_general') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="quantite" class="tcd-label">Quantité (articles) <span class="tcd-required">*</span></label>
                <input type="number" id="quantite" name="quantite" min="1" max="500" step="1" value="{{ old('quantite', $don->quantite) }}" @class(['tcd-input', 'is-invalid' => $errors->has('quantite')]) required>
                @error('quantite') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="v-col-md-6 v-col-12 py-0">
              <div class="tcd-field">
                <label for="poids_kg" class="tcd-label">Poids estimé (kg)</label>
                <input type="number" id="poids_kg" name="poids_kg" min="0.1" step="0.01" value="{{ old('poids_kg', $don->poids_kg) }}" @class(['tcd-input', 'is-invalid' => $errors->has('poids_kg')])>
                @error('poids_kg') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="v-col-12 py-0">
              <div class="tcd-field">
                <label for="message" class="tcd-label">Message</label>
                <textarea id="message" name="message" rows="3" @class(['tcd-input', 'is-invalid' => $errors->has('message')])>{{ old('message', $don->message) }}</textarea>
                @error('message') <div class="tcd-error">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-md-4 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Remise &amp; statut</div></div></div>
        <div class="v-card-text">
          <div class="tcd-field">
            <label for="mode_remise" class="tcd-label">Mode de remise <span class="tcd-required">*</span></label>
            <select id="mode_remise" name="mode_remise" @class(['tcd-input', 'is-invalid' => $errors->has('mode_remise')]) required data-toggle-collecte>
              @foreach (\App\Models\Don::MODES as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected($mode === $optValue)>{{ $optLabel }}</option>
              @endforeach
            </select>
            @error('mode_remise') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field" id="adresse-collecte-group" @if ($mode !== 'collecte_a_domicile') hidden @endif>
            <label for="adresse_collecte" class="tcd-label">Adresse de collecte <span class="tcd-required">*</span></label>
            <input type="text" id="adresse_collecte" name="adresse_collecte" value="{{ old('adresse_collecte', $don->adresse_collecte) }}" @class(['tcd-input', 'is-invalid' => $errors->has('adresse_collecte')])>
            @error('adresse_collecte') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field">
            <label for="date_remise" class="tcd-label">Date de remise <span class="tcd-required">*</span></label>
            <input type="date" id="date_remise" name="date_remise" value="{{ old('date_remise', $don->date_remise?->format('Y-m-d')) }}" @class(['tcd-input', 'is-invalid' => $errors->has('date_remise')]) required>
            @error('date_remise') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>

          <div class="tcd-field mb-0">
            <label for="statut" class="tcd-label">Statut <span class="tcd-required">*</span></label>
            <select id="statut" name="statut" @class(['tcd-input', 'is-invalid' => $errors->has('statut')]) required>
              @foreach (\App\Models\Don::STATUTS as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected(old('statut', $don->statut) === $optValue)>{{ $optLabel }}</option>
              @endforeach
            </select>
            @error('statut') <div class="tcd-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-12 d-flex gap-3">
      @include('admin.dons.partials.btn', ['label' => $don->exists ? 'Enregistrer les modifications' : 'Enregistrer le don', 'icon' => 'bx-save'])
      @include('admin.dons.partials.btn', ['href' => $don->exists ? route('admin.dons.dons.show', $don) : route('admin.dons.dons.index'), 'label' => 'Annuler', 'variant' => 'tonal', 'color' => 'secondary'])
    </div>
  </div>
</form>

@include('dons.partials.collecte-toggle')
