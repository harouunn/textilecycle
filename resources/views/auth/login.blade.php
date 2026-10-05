@extends('layouts.front')

@section('title', 'Connexion')

@section('content')
  <section class="bg-light py-5">
    <div class="container py-md-5">
      <div class="tc-auth-card p-4 p-md-5">
        <h2 class="section-title text-center mb-2">Connexion</h2>
        <p class="text-center mb-4">Heureux de vous revoir sur TexTileCycle.</p>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('email')])>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('password')])>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
              <input id="remember_me" type="checkbox" name="remember" class="form-check-input m-0">
              <label for="remember_me" class="form-check-label">Se souvenir de moi</label>
            </div>
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="item-anchor">Mot de passe oublié ?</a>
            @endif
          </div>

          <button type="submit" class="btn btn-dark btn-lg text-uppercase w-100">Se connecter</button>
        </form>

        @if (Route::has('register'))
          <p class="text-center mt-4 mb-0">Pas encore de compte ? <a href="{{ route('register') }}" class="item-anchor">Inscrivez-vous</a></p>
        @endif
      </div>
    </div>
  </section>
@endsection
