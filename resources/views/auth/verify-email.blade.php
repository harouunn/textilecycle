@extends('layouts.front')

@section('title', 'Vérification de l\'adresse e-mail')

@section('content')
  <section class="bg-light py-5">
    <div class="container py-md-5">
      <div class="tc-auth-card p-4 p-md-5">
        <h2 class="section-title text-center mb-3">Vérifiez votre e-mail</h2>
        <p class="mb-4">Merci pour votre inscription ! Avant de commencer, veuillez confirmer votre adresse e-mail en cliquant
          sur le lien que nous venons de vous envoyer. Si vous ne l'avez pas reçu, nous pouvons vous en renvoyer un.</p>

        @if (session('status') == 'verification-link-sent')
          <div class="alert alert-success">Un nouveau lien de vérification a été envoyé à votre adresse e-mail.</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
          @csrf
          <button type="submit" class="btn btn-dark btn-lg text-uppercase w-100">Renvoyer l'e-mail</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
          @csrf
          <button type="submit" class="btn btn-link item-anchor">Se déconnecter</button>
        </form>
      </div>
    </div>
  </section>
@endsection
