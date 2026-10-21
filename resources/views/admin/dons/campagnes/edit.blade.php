@extends('layouts.back')

@section('title', 'Modifier '.$campagne->titre)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.campagnes.show', $campagne) }}" class="text-body-2">‹ Retour à la campagne</a>
  <h4 class="text-h4 mt-2 mb-0">Modifier « {{ $campagne->titre }} »</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.dons.campagnes._form', [
  'action' => route('admin.dons.campagnes.update', $campagne),
  'method' => 'PUT',
])
@endsection
