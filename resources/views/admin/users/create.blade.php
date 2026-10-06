@extends('layouts.back')

@section('title', 'Nouvel utilisateur')

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.users.index') }}" class="text-body-2">‹ Retour aux utilisateurs</a>
  <h4 class="text-h4 mt-2 mb-0">Nouvel utilisateur</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.users._form', [
  'action' => route('admin.users.store'),
  'method' => 'POST',
])
@endsection
