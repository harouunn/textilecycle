@extends('layouts.front')

@section('title', 'Espace association')

@section('content')
@php($hasFilters = $filters['q'] !== '' || $filters['association_id'] || $filters['statut'] || $filters['type_article'])

<div class="tc-page">
  <x-ui.hero surtitre="Espace association" titre="Dons reçus" :sous-titre="$associations->pluck('nom')->join(' · ')"
    :fil="['Dons' => route('dons.index'), 'Espace association' => null]">
    <a href="{{ route('dons.espace.creneaux.index') }}" class="tc-btn">Créneaux de collecte</a>
    <a href="{{ route('dons.mes-dons') }}" class="tc-btn">Mes dons</a>
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    <div class="d-flex flex-wrap gap-3 small mb-3">
      @foreach ($associations as $association)
        <span>
          <strong>{{ $association->nom }}</strong> :
          <a href="{{ route('dons.espace.presentation.edit', $association) }}">présentation</a> ·
          <a href="{{ route('dons.espace.rapport', $association) }}">rapport mensuel</a>
        </span>
      @endforeach
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
      @foreach (\App\Enums\Dons\DonStatut::cases() as $statut)
        <a href="{{ route('dons.espace.index', [...array_filter($filters), 'statut' => $statut->value]) }}"
          @class(['badge rounded-pill text-decoration-none', 'text-bg-'.$statut->color() => $filters['statut'] === $statut->value, 'text-bg-light border' => $filters['statut'] !== $statut->value])>
          {{ $statut->label() }} · {{ $compteurs[$statut->value] ?? 0 }}
        </a>
      @endforeach
    </div>

    <form method="GET" action="{{ route('dons.espace.index') }}" class="row g-2 align-items-end mb-4">
      <div class="col-md-3">
        <label for="q" class="form-label small">Rechercher</label>
        <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Donateur, message…">
      </div>
      @if ($associations->count() > 1)
        <div class="col-md-3">
          <label for="association_id" class="form-label small">Association</label>
          <select id="association_id" name="association_id" class="form-select">
            <option value="">Toutes</option>
            @foreach ($associations as $association)
              <option value="{{ $association->id }}" @selected((string) $filters['association_id'] === (string) $association->id)>{{ $association->nom }}</option>
            @endforeach
          </select>
        </div>
      @endif
      <div class="col-md-2">
        <label for="statut" class="form-label small">Statut</label>
        <select id="statut" name="statut" class="form-select">
          <option value="">Tous</option>
          @foreach (\App\Enums\Dons\DonStatut::options() as $value => $label)
            <option value="{{ $value }}" @selected($filters['statut'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label for="type_article" class="form-label small">Type d'article</label>
        <select id="type_article" name="type_article" class="form-select">
          <option value="">Tous</option>
          @foreach (\App\Enums\Dons\TypeArticle::options() as $value => $label)
            <option value="{{ $value }}" @selected($filters['type_article'] === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-dark">Filtrer</button>
        @if ($hasFilters)
          <a href="{{ route('dons.espace.index') }}" class="btn btn-link">Réinitialiser</a>
        @endif
      </div>
    </form>

    @if ($dons->isEmpty())
      <p class="text-center text-muted py-5">{{ $hasFilters ? 'Aucun don ne correspond à ces critères.' : 'Aucun don pour le moment.' }}</p>
    @else
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>N°</th>
              <th>Donateur</th>
              <th>Article</th>
              <th class="text-center">Qté</th>
              <th>Remise</th>
              <th>Statut</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($dons as $don)
              <tr>
                <td>
                  <a href="{{ route('dons.espace.show', $don) }}">#{{ $don->id }}</a>
                  @if ($associations->count() > 1)
                    <div class="small text-muted">{{ $don->association->nom }}</div>
                  @endif
                </td>
                <td>{{ $don->user->name }}</td>
                <td>
                  {{ $don->typeLabel() }}
                  <div class="small text-muted">{{ $don->etatLabel() }}{{ $don->poids_kg ? ' · '.number_format($don->poids_kg, 2, ',', ' ').' kg' : '' }}</div>
                </td>
                <td class="text-center">{{ $don->quantite }}</td>
                <td>
                  {{ $don->date_remise->format('d/m/Y') }}
                  <div class="small text-muted">{{ $don->modeLabel() }}</div>
                </td>
                <td>@include('dons.partials.statut-badge', ['don' => $don])</td>
                <td class="text-end">@include('dons.espace._actions', ['don' => $don])</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-4">
        {{ $dons->links('dons.partials.pagination') }}
      </div>
    @endif
  </div>
</section>
@endsection
