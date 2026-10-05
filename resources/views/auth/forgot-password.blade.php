@extends('layouts.front')

@section('title', 'Mot de passe oublié')

@section('content')
  <section class="bg-light py-5">
    <div class="container py-md-5">
      <div class="tc-auth-card p-4 p-md-5">
        <h2 class="section-title text-center mb-2">Mot de passe oublié</h2>
        <p class="text-center mb-4">Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
          @csrf

          <div class="mb-4">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
              @class(['form-control form-control-lg', 'is-invalid' => $errors->has('email')])>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <button type="submit" class="btn btn-dark btn-lg text-uppercase w-100">Envoyer le lien</button>
        </form>

        <p class="text-center mt-4 mb-0"><a href="{{ route('login') }}" class="item-anchor">Retour à la connexion</a></p>
      </div>
    </div>
  </section>
@endsection
