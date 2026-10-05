@extends('layouts.front')

@section('title', 'Mes dons')

@section('content')
<section class="bg-light py-5">
  <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <h1 class="section-title mt-3 mb-1">Mes dons</h1>
      <p class="mb-0 text-muted">Suivez l'avancement des dons que vous avez proposés.</p>
    </div>
    <a href="{{ route('dons.index') }}" class="btn btn-dark rounded-pill">Faire un nouveau don</a>
  </div>
</section>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    @if ($dons->isEmpty())
      <div class="text-center py-5">
        <p class="text-muted">Vous n'avez encore proposé aucun don.</p>
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
                  @if ($don->association->active)
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
                  @if ($don->isCancellable())
                    <form method="POST" action="{{ route('dons.cancel', $don) }}" onsubmit="return confirm('Annuler ce don ?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm">Annuler</button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-4">
        {{ $dons->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>
</section>
@endsection
