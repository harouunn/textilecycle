@extends('layouts.front')

@section('title', 'Demander une réparation')

@section('content')
  @include('ateliers.partials.styles')

  <section class="bg-light py-5">
    <div class="container py-md-4" style="max-width: 900px;">
      <h1 class="section-title text-center mb-2">Demander une réparation</h1>
      <p class="text-center mb-5">
        Atelier : <a href="{{ route('ateliers.show', $atelier) }}" class="item-anchor">{{ $atelier->nom }}</a>, {{ $atelier->ville }}.<br>
        Décrivez le problème, ajoutez une photo, ou les deux : nous estimons aussitôt le coût et le délai.
      </p>

      <div class="bg-white p-4 p-md-5">
        @include('ateliers.partials.flash')

        <form method="POST" action="{{ route('ateliers.demandes.store', $atelier) }}" enctype="multipart/form-data">
          @csrf

          <div class="row g-4 tc-atelier-form">
            <div class="col-md-7">
              <label for="titre" class="form-label">Titre *</label>
              <input id="titre" type="text" name="titre" value="{{ old('titre', $demande->titre) }}" required maxlength="150" placeholder="ex. Jean troué au genou"
                @class(['form-control form-control-lg', 'is-invalid' => $errors->has('titre')])>
              @error('titre')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-5">
              <label for="type_vetement" class="form-label">Type de vêtement *</label>
              <select id="type_vetement" name="type_vetement" required @class(['form-select form-select-lg', 'is-invalid' => $errors->has('type_vetement')])>
                @foreach (\App\Enums\Ateliers\TypeVetement::options() as $value => $label)
                  <option value="{{ $value }}" @selected(old('type_vetement', $demande->type_vetement?->value) === $value)>{{ $label }}</option>
                @endforeach
              </select>
              @error('type_vetement')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label for="description" class="form-label">Description du problème</label>
              <textarea id="description" name="description" rows="5" maxlength="2000"
                placeholder="ex. Trou de 3 cm au genou gauche, et la fermeture éclair ne remonte plus."
                @class(['form-control', 'is-invalid' => $errors->has('description')])>{{ old('description', $demande->description) }}</textarea>
              <div class="form-text">Plus vous êtes précis (bouton, ourlet, trou, fermeture, couture, doublure, matière…), plus l'estimation est juste.</div>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label for="photo" class="form-label">Photo (JPG, PNG ou WEBP, 2 Mo max.)</label>
              <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                @class(['form-control', 'is-invalid' => $errors->has('photo')])>
              @error('photo')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12 d-flex flex-wrap gap-3 mt-4">
              <button type="submit" class="btn btn-dark btn-lg text-uppercase">Obtenir mon diagnostic</button>
              <a href="{{ route('ateliers.show', $atelier) }}" class="btn btn-outline-dark btn-lg text-uppercase">Annuler</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>
@endsection
