@extends('layouts.back')

@section('title', 'Modifier le don n°'.$don->id)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.dons.show', $don) }}" class="text-body-2">‹ Retour au don</a>
  <h4 class="text-h4 mt-2 mb-0">Modifier le don n°{{ $don->id }}</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.dons.dons._form', [
  'action' => route('admin.dons.dons.update', $don),
  'method' => 'PUT',
])
@endsection
