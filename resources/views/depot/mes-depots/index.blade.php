@extends('layouts.front')

@section('title', 'Mes dépôts')

@section('content')
  @include('depot.partials.styles')

  <section class="bg-light py-5">
    <div class="container text-center py-md-4">
      <h1 class="section-title">Mes dépôts</h1>
      <p class="mb-4">Retrouvez les vêtements que vous avez confiés à TexTileCycle.</p>
      <a href="{{ route('depot.mes-depots.create') }}" class="btn btn-dark text-uppercase">Déposer un vêtement</a>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      @include('depot.partials.flash')

      @if ($vetements->isEmpty())
        <p class="text-center py-5">Vous n'avez encore déposé aucun vêtement.</p>
      @else
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr class="text-uppercase small">
                <th scope="col">Vêtement</th>
                <th scope="col">Catégorie</th>
                <th scope="col">Taille</th>
                <th scope="col">État</th>
                <th scope="col">Statut</th>
                <th scope="col">Déposé le</th>
                <th scope="col" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($vetements as $vetement)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-3">
                      <img src="{{ $vetement->photo_url }}" alt="" class="tc-thumb">
                      <a href="{{ route('depot.catalogue.show', $vetement) }}" class="item-anchor">{{ $vetement->titre }}</a>
                    </div>
                  </td>
                  <td>{{ $vetement->categorie->nom }}</td>
                  <td>{{ $vetement->taille->label() }}</td>
                  <td>{{ $vetement->etat->label() }}</td>
                  <td><span class="badge text-bg-{{ $vetement->statut->color() }}">{{ $vetement->statut->label() }}</span></td>
                  <td>{{ $vetement->date_depot->format('d/m/Y') }}</td>
                  <td class="text-end text-nowrap">
                    <a href="{{ route('depot.mes-depots.edit', $vetement) }}" class="btn btn-sm btn-outline-dark">Modifier</a>
                    <form method="POST" action="{{ route('depot.mes-depots.destroy', $vetement) }}" class="d-inline" onsubmit="return confirm(@js("Supprimer « {$vetement->titre} » ?"))">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          {{ $vetements->links('pagination::bootstrap-5') }}
        </div>
      @endif
    </div>
  </section>
@endsection
