@extends('layouts.front')

@section('title', 'Confirmation du mot de passe')

@section('content')
  <x-ui.auth surtitre="Zone sécurisée" titre="Confirmez votre identité" sous-titre="Veuillez confirmer votre mot de passe pour continuer."
    image="banner-image-6.jpg" accroche="Une vérification de plus pour protéger votre compte.">
    <form method="POST" action="{{ route('password.confirm') }}" class="tc-form" novalidate>
      @csrf
      <x-ui.mot-de-passe id="password" label="Mot de passe" autocomplete="current-password" required />
      <button type="submit" class="tc-btn tc-btn--bloc mt-2"><x-ui.icone nom="bouclier" /> Confirmer</button>
    </form>
  </x-ui.auth>
@endsection
