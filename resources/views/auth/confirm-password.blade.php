@extends('layouts.front')

@section('title', 'Confirmation du mot de passe')

@section('content')
  <section class="bg-light py-5">
    <div class="container py-md-5">
      <div class="tc-auth-card p-4 p-md-5">
        <h2 class="section-title text-center mb-3">Zone sécurisée</h2>
        <p class="mb-4">Veuillez confirmer votre mot de passe pour continuer.</p>

        <form method="POST" action="{{ route('password.confirm') }}">
          @csrf

          <div class="mb-4">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('password')])>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <button type="submit" class="btn btn-dark btn-lg text-uppercase w-100">Confirmer</button>
        </form>
      </div>
    </div>
  </section>
@endsection
