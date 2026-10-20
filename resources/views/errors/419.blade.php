@extends('errors.layout')

@section('code', '419')
@section('titre', 'Page expirée')
@section('message', 'La page est restée ouverte longtemps et la session a expiré, par sécurité. Revenez en arrière, actualisez la page puis recommencez.')
@section('action')
  <a class="secondaire" href="javascript:history.back()">Revenir en arrière</a>
@endsection
