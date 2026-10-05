@extends('layouts.back')

@section('title', 'Modifier '.$association->nom)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.associations.show', $association) }}" class="text-body-2">‹ Retour à l'association</a>
  <h4 class="text-h4 mt-2 mb-0">Modifier « {{ $association->nom }} »</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.dons.associations._form', [
  'action' => route('admin.dons.associations.update', $association),
  'method' => 'PUT',
])
@endsection
