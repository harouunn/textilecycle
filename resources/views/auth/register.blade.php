@extends('layouts.front')

@section('title', 'Inscription')

@section('content')
  <section class="bg-light py-5">
    <div class="container py-md-5">
      <div class="tc-auth-card p-4 p-md-5">
        <h2 class="section-title text-center mb-2">Inscription</h2>
        <p class="text-center mb-4">Créez votre compte et donnez une seconde vie à vos vêtements.</p>

        <form method="POST" action="{{ route('register') }}">
          @csrf

          <div class="mb-3">
            <label for="name" class="form-label">Nom complet</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('name')])>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('email')])>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('password')])>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('password_confirmation')])>
            @error('password_confirmation')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <button type="submit" class="btn btn-dark btn-lg text-uppercase w-100">Créer mon compte</button>
        </form>

        <p class="text-center mt-4 mb-0">Déjà inscrit ? <a href="{{ route('login') }}" class="item-anchor">Connectez-vous</a></p>
      </div>
    </div>
  </section>
@endsection
