@extends('layouts.back')

@section('title', 'Nouvelle campagne')

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.campagnes.index') }}" class="text-body-2">‹ Retour aux campagnes</a>
  <h4 class="text-h4 mt-2 mb-0">Nouvelle campagne de collecte</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.dons.campagnes._form', [
  'action' => route('admin.dons.campagnes.store'),
  'method' => 'POST',
])
@endsection
