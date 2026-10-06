@extends('layouts.front')

@section('title', 'Mes projets d\'upcycling')

@section('content')
@include('upcycling.partials.styles')

<section class="bg-light py-5">
  <div class="container py-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="section-title mb-1">Mes projets</h1>
        <p class="mb-0">Les projets proposés sont publiés après validation par l'équipe TexTileCycle.</p>
      </div>
      <a href="{{ route('upcycling.projets.create') }}" class="btn btn-dark text-uppercase">Proposer un projet</a>
    </div>

    @include('upcycling.partials.flash')

    @if ($projets->isEmpty())
      <div class="bg-white border p-5 text-center">
        <p class="mb-3">Vous n'avez encore proposé aucun projet.</p>
        <a href="{{ route('upcycling.projets.create') }}" class="btn btn-outline-dark text-uppercase">Proposer mon premier projet</a>
      </div>
    @else
      <div class="table-responsive bg-white border">
        <table class="table align-middle mb-0">
          <thead>
            <tr class="text-uppercase small">
              <th scope="col" class="ps-4">Projet</th>
              <th scope="col">Difficulté</th>
              <th scope="col">Durée</th>
              <th scope="col" class="text-center">Étapes</th>
              <th scope="col">Statut</th>
              <th scope="col" class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($projets as $projet)
              <tr>
                <td class="ps-4">
                  <a href="{{ route('upcycling.projets.show', $projet) }}" class="item-anchor fw-bold">{{ $projet->titre }}</a>
                  <div class="tcu-meta">{{ $projet->vetement_origine }} → {{ $projet->resultat }}</div>
                </td>
                <td>@include('upcycling.partials.badge', ['enum' => $projet->difficulte])</td>
                <td class="text-nowrap">{{ $projet->dureeFormatee() }}</td>
                <td class="text-center">{{ $projet->etapes_count }}</td>
                <td>@include('upcycling.partials.badge', ['enum' => $projet->statut])</td>
                <td class="text-end pe-4 text-nowrap">
                  <a href="{{ route('upcycling.projets.etapes.index', $projet) }}" class="btn btn-sm btn-outline-dark">Étapes</a>
                  <a href="{{ route('upcycling.projets.edit', $projet) }}" class="btn btn-sm btn-outline-dark">Modifier</a>
                  <form method="POST" action="{{ route('upcycling.projets.destroy', $projet) }}" class="d-inline"
                    onsubmit="return confirm('Supprimer définitivement ce projet et toutes ses étapes ?')">
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

      <div class="d-flex justify-content-center mt-4">
        {{ $projets->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>
</section>
@endsection
