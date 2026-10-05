@extends('layouts.front')

@section('title', 'Nouveau mot de passe')

@section('content')
  <section class="bg-light py-5">
    <div class="container py-md-5">
      <div class="tc-auth-card p-4 p-md-5">
        <h2 class="section-title text-center mb-4">Nouveau mot de passe</h2>

        <form method="POST" action="{{ route('password.store') }}">
          @csrf
          <input type="hidden" name="token" value="{{ $request->route('token') }}">

          <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('email')])>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Nouveau mot de passe</label>
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

          <button type="submit" class="btn btn-dark btn-lg text-uppercase w-100">Réinitialiser le mot de passe</button>
        </form>
      </div>
    </div>
  </section>
@endsection
