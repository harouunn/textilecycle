@extends('layouts.front')

@section('title', 'Mes dons')

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Mon espace" titre="Mes dons" sous-titre="Suivez l'avancement des dons que vous avez proposés."
    :fil="['Dons' => route('dons.index'), 'Mes dons' => null]">
    <a href="{{ route('dons.index') }}" class="tc-btn">Faire un nouveau don</a>
    <a href="{{ route('dons.mon-impact') }}" class="tc-btn">Mon impact</a>
    @if ($estResponsable)
      <a href="{{ route('dons.espace.index') }}" class="tc-btn">Espace association</a>
    @endif
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    @if ($dons->isEmpty())
      <div class="text-center py-5">
        <p class="text-muted mb-1">Aucun don pour le moment.</p>
        <p class="small text-muted">Proposez vos vêtements en bon état à une association partenaire : vous suivrez ici chaque étape.</p>
        <a href="{{ route('dons.index') }}" class="btn btn-outline-dark rounded-pill">Découvrir les associations</a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Association</th>
              <th>Article</th>
              <th class="text-center">Quantité</th>
              <th>Remise</th>
              <th>Statut</th>
              <th class="text-end"></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($dons as $don)
              <tr>
                <td>
                  @if ($don->association->active && ! $don->association->trashed())
                    <a href="{{ route('dons.associations.show', $don->association) }}">{{ $don->association->nom }}</a>
                  @else
                    {{ $don->association->nom }}
                  @endif
                  <div class="small text-muted">Proposé le {{ $don->created_at->format('d/m/Y') }}</div>
                </td>
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
                <td class="text-end">
                  <div class="d-inline-flex gap-2">
                    <a href="{{ route('dons.mes-dons.show', $don) }}" class="btn btn-outline-dark btn-sm">Suivi</a>
                    @can('telechargerRecu', $don)
                      <a href="{{ route('dons.mes-dons.recu', $don) }}" class="btn btn-dark btn-sm" title="Reçu {{ $don->reference }}">Reçu PDF</a>
                    @endcan
                    @can('cancel', $don)
                      <form method="POST" action="{{ route('dons.cancel', $don) }}" onsubmit="return confirm('Annuler ce don ?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Annuler</button>
                      </form>
                    @endcan
                  </div>
                </td>
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
@include('dons.partials.assistant')
@endsection
