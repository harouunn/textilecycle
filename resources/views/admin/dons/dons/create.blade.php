@extends('layouts.back')

@section('title', 'Nouveau don')

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.dons.index') }}" class="text-body-2">‹ Retour aux dons</a>
  <h4 class="text-h4 mt-2 mb-0">Nouveau don</h4>
</div>

@include('admin.dons.partials.flash')

@include('admin.dons.dons._form', [
  'action' => route('admin.dons.dons.store'),
  'method' => 'POST',
])
@endsection
