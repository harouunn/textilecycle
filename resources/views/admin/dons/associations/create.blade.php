@extends('layouts.back')

@section('title', 'Nouvelle association')

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.associations.index') }}" class="text-body-2">‹ Retour aux associations</a>
  <h4 class="text-h4 mt-2 mb-0">Nouvelle association</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.dons.associations._form', [
  'action' => route('admin.dons.associations.store'),
  'method' => 'POST',
])
@endsection
