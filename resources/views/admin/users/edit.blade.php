@extends('layouts.back')

@section('title', 'Modifier '.$user->name)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.users.show', $user) }}" class="text-body-2">‹ Retour au compte</a>
  <h4 class="text-h4 mt-2 mb-0">Modifier le compte de {{ $user->name }}</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.users._form', [
  'action' => route('admin.users.update', $user),
  'method' => 'PUT',
])
@endsection
