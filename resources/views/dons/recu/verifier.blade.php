@extends('layouts.front')

@section('title', 'Vérification du reçu '.$reference)

@section('content')
<section class="py-5">
  <div class="container" style="max-width: 640px">
    @if ($don)
      <div class="alert alert-success" role="status">
        <strong>Reçu authentique.</strong> Ce reçu a bien été émis par TexTileCycle.
      </div>
      <dl class="row">
        <dt class="col-sm-5">Référence</dt><dd class="col-sm-7">{{ $don->reference }}</dd>
        <dt class="col-sm-5">Association</dt><dd class="col-sm-7">{{ $don->association->nom }} ({{ $don->association->ville }})</dd>
        <dt class="col-sm-5">Donateur</dt><dd class="col-sm-7">{{ \Illuminate\Support\Str::of($don->user?->name)->trim()->explode(' ')->first() ?: '—' }}</dd>
        <dt class="col-sm-5">Articles</dt><dd class="col-sm-7">{{ $don->quantite }} × {{ $don->typeLabel() }}</dd>
        <dt class="col-sm-5">Poids</dt><dd class="col-sm-7">{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</dd>
        <dt class="col-sm-5">Statut</dt><dd class="col-sm-7">@include('dons.partials.statut-badge', ['don' => $don])</dd>
      </dl>
    @else
      <div class="alert alert-danger" role="alert">
        <strong>Reçu introuvable.</strong> Aucun don ne porte la référence {{ $reference }}. Vérifiez la référence ou contactez l'association.
      </div>
    @endif
    <a href="{{ route('dons.index') }}" class="btn btn-link px-0">‹ Associations & dons</a>
  </div>
</section>
@endsection
