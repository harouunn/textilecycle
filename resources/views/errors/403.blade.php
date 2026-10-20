@extends('errors.layout')

@section('code', '403')
@section('titre', 'Accès réservé')
@section('message', 'Cette page est réservée à l’équipe TexTileCycle ou à un autre membre. Si vous pensez que c’est une erreur, reconnectez-vous avec le bon compte.')
@section('action')
  <a class="secondaire" href="{{ url('/login') }}">Se connecter</a>
@endsection
