@extends('layouts.back')

@section('title', 'Nouvel atelier')

@section('content')
<div class="v-row">
  <div class="v-col-12">
    <nav aria-label="breadcrumb" class="d-flex align-center mb-4">
      <ol class="v-breadcrumbs v-theme--light v-breadcrumbs--density-default">
        <li class="v-breadcrumbs-item">
          <a href="{{ route('admin.ateliers.index') }}" class="v-breadcrumbs-item--link">Ateliers</a>
        </li>
        <li aria-hidden="true" class="v-breadcrumbs-divider">/</li>
        <li class="v-breadcrumbs-item v-breadcrumbs-item--disabled">
          <span class="v-breadcrumbs-item--link">Nouveau</span>
        </li>
      </ol>
    </nav>

    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Nouvel atelier</div>
          <div class="v-card-subtitle">Ajouter un nouvel atelier partenaire</div>
        </div>
      </div>

      <div class="v-card-text">
        <form action="{{ route('admin.ateliers.store') }}" method="POST">
          @csrf

          <div class="v-row">
            {{-- Nom --}}
            <div class="v-col-md-6 v-col-12">
              <label class="v-label v-field-label" for="nom">Nom de l'atelier <span class="text-error">*</span></label>
              <input 
                type="text" 
                id="nom" 
                name="nom" 
                class="v-field__input @error('nom') error @enderror" 
                value="{{ old('nom') }}" 
                required
              >
              @error('nom')
                <div class="v-messages v-messages--active text-error mt-1">
                  <div class="v-messages__message">{{ $message }}</div>
                </div>
              @enderror
            </div>

            {{-- Statut --}}
            <div class="v-col-md-6 v-col-12">
              <label class="v-label v-field-label" for="actif">Statut</label>
              <div class="mt-2">
                <label class="v-label" style="cursor: pointer;">
                  <input 
                    type="checkbox" 
                    id="actif" 
                    name="actif" 
                    value="1"
                    {{ old('actif', true) ? 'checked' : '' }}
                  >
                  <span class="ms-2">Atelier actif</span>
                </label>
              </div>
            </div>

            {{-- Adresse --}}
            <div class="v-col-12">
              <label class="v-label v-field-label" for="adresse">Adresse <span class="text-error">*</span></label>
              <input 
                type="text" 
                id="adresse" 
                name="adresse" 
                class="v-field__input @error('adresse') error @enderror" 
                value="{{ old('adresse') }}" 
                required
              >
              @error('adresse')
                <div class="v-messages v-messages--active text-error mt-1">
                  <div class="v-messages__message">{{ $message }}</div>
                </div>
              @enderror
            </div>

            {{-- Ville --}}
            <div class="v-col-md-8 v-col-12">
              <label class="v-label v-field-label" for="ville">Ville <span class="text-error">*</span></label>
              <input 
                type="text" 
                id="ville" 
                name="ville" 
                class="v-field__input @error('ville') error @enderror" 
                value="{{ old('ville') }}" 
                required
              >
              @error('ville')
                <div class="v-messages v-messages--active text-error mt-1">
                  <div class="v-messages__message">{{ $message }}</div>
                </div>
              @enderror
            </div>

            {{-- Code postal --}}
            <div class="v-col-md-4 v-col-12">
              <label class="v-label v-field-label" for="code_postal">Code postal <span class="text-error">*</span></label>
              <input 
                type="text" 
                id="code_postal" 
                name="code_postal" 
                class="v-field__input @error('code_postal') error @enderror" 
                value="{{ old('code_postal') }}" 
                required
              >
              @error('code_postal')
                <div class="v-messages v-messages--active text-error mt-1">
                  <div class="v-messages__message">{{ $message }}</div>
                </div>
              @enderror
            </div>

            {{-- Téléphone --}}
            <div class="v-col-md-6 v-col-12">
              <label class="v-label v-field-label" for="telephone">Téléphone</label>
              <input 
                type="text" 
                id="telephone" 
                name="telephone" 
                class="v-field__input @error('telephone') error @enderror" 
                value="{{ old('telephone') }}"
              >
              @error('telephone')
                <div class="v-messages v-messages--active text-error mt-1">
                  <div class="v-messages__message">{{ $message }}</div>
                </div>
              @enderror
            </div>

            {{-- Email --}}
            <div class="v-col-md-6 v-col-12">
              <label class="v-label v-field-label" for="email">Email</label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="v-field__input @error('email') error @enderror" 
                value="{{ old('email') }}"
              >
              @error('email')
                <div class="v-messages v-messages--active text-error mt-1">
                  <div class="v-messages__message">{{ $message }}</div>
                </div>
              @enderror
            </div>

            {{-- Description --}}
            <div class="v-col-12">
              <label class="v-label v-field-label" for="description">Description</label>
              <textarea 
                id="description" 
                name="description" 
                class="v-field__input @error('description') error @enderror" 
                rows="4"
              >{{ old('description') }}</textarea>
              @error('description')
                <div class="v-messages v-messages--active text-error mt-1">
                  <div class="v-messages__message">{{ $message }}</div>
                </div>
              @enderror
            </div>

            {{-- Boutons --}}
            <div class="v-col-12">
              <div class="d-flex gap-3 flex-wrap">
                <button type="submit" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated">
                  <span class="v-btn__overlay"></span>
                  <span class="v-btn__underlay"></span>
                  <span class="v-btn__content">Enregistrer</span>
                </button>
                <a href="{{ route('admin.ateliers.index') }}" class="v-btn v-theme--light v-btn--density-default v-btn--size-default v-btn--variant-outlined">
                  <span class="v-btn__overlay"></span>
                  <span class="v-btn__underlay"></span>
                  <span class="v-btn__content">Annuler</span>
                </a>
              </div>
            </div>
          </div>
        </form>
      </div>

      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
