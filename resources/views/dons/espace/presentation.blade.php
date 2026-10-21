@extends('layouts.front')

@section('title', 'Présentation — '.$association->nom)

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Espace association" titre="Présentation de l'association" :sous-titre="$association->nom"
    :fil="['Dons' => route('dons.index'), 'Espace association' => route('dons.espace.index'), 'Présentation' => null]">
    <a href="{{ route('dons.espace.rapport', $association) }}" class="tc-btn">Rapport mensuel</a>
    @if ($association->active)
      <a href="{{ route('dons.associations.show', $association) }}" class="tc-btn">Voir la page publique</a>
    @endif
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container" style="max-width: 820px">
    @include('dons.partials.flash')

    <form method="POST" action="{{ route('dons.espace.presentation.update', $association) }}" novalidate>
      @csrf
      @method('PUT')
      <input type="hidden" id="ia-nom" value="{{ $association->nom }}">
      <input type="hidden" id="ia-ville" value="{{ $association->ville }}">

      <div class="mb-4">
        <label for="besoins" class="form-label">Besoins (un par ligne) <span class="text-danger">*</span></label>
        <textarea id="besoins" name="besoins" rows="4" @class(['form-control', 'is-invalid' => $errors->has('besoins')]) required>{{ old('besoins', $association->besoins) }}</textarea>
        @error('besoins') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="mb-4">
        <div class="d-flex justify-content-between align-items-end gap-2 mb-1">
          <label for="description" class="form-label mb-0">Description <span class="text-danger">*</span></label>
          <button type="button" class="btn btn-sm btn-outline-dark" data-ia-rediger data-type="description" data-cible="description"
            data-fixes="{{ json_encode(['association_id' => $association->id]) }}"
            data-champs="{{ json_encode(['nom' => '#ia-nom', 'ville' => '#ia-ville', 'besoins' => '#besoins', 'description' => '#description']) }}">✨ Générer avec l'IA</button>
        </div>
        <textarea id="description" name="description" rows="8" @class(['form-control', 'is-invalid' => $errors->has('description')]) required>{{ old('description', $association->description) }}</textarea>
        <div data-ia-message="description"></div>
        @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-text">Le texte proposé n'est enregistré que lorsque vous cliquez sur « Enregistrer ».</div>
      </div>

      <button type="submit" class="btn btn-dark rounded-pill px-5">Enregistrer</button>
      <a href="{{ route('dons.espace.index') }}" class="btn btn-link">Annuler</a>
    </form>
  </div>
</section>

@include('dons.partials.ia-rediger')
@endsection
