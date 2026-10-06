@extends('layouts.back')

@php($aValider = ($filters['moderation'] ?? null) === \App\Enums\Depot\Moderation::EnAttente->value)

@section('title', $aValider ? 'Dépôts à valider' : 'Vêtements')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">{{ $aValider ? 'Dépôts à valider' : 'Dépôt & vêtements' }}</div>
        <div class="v-card-subtitle">
          {{ $aValider ? $vetements->total().' dépôt(s) de clients en attente : approuvez-les pour les publier dans le catalogue.' : $vetements->total().' vêtement(s)' }}
        </div>
      </div>
      <div class="v-card-item__append">
        @include('admin.depot.partials.btn', ['label' => 'Ajouter un vêtement', 'icon' => 'bx-plus', 'href' => route('admin.depot.vetements.create')])
      </div>
    </div>

    {{-- Recherche et filtres --}}
    <div class="v-card-text">
      <form method="GET" action="{{ route('admin.depot.vetements.index') }}">
        <div class="v-row">
          <div class="v-col-md-3 v-col-sm-6 v-col-12">
            <label for="search" class="tc-label">Titre</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Rechercher par titre…" class="tc-control">
          </div>
          <div class="v-col-md-2 v-col-sm-6 v-col-12">
            <label for="categorie" class="tc-label">Catégorie</label>
            <select id="categorie" name="categorie" class="tc-control">
              <option value="">Toutes</option>
              @foreach ($categories as $id => $nom)
                <option value="{{ $id }}" @selected(($filters['categorie'] ?? null) == $id)>{{ $nom }}</option>
              @endforeach
            </select>
          </div>
          <div class="v-col-md-2 v-col-sm-6 v-col-12">
            <label for="etat" class="tc-label">État</label>
            <select id="etat" name="etat" class="tc-control">
              <option value="">Tous</option>
              @foreach (\App\Enums\Depot\Etat::options() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['etat'] ?? null) === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="v-col-md-2 v-col-sm-6 v-col-12">
            <label for="statut" class="tc-label">Statut</label>
            <select id="statut" name="statut" class="tc-control">
              <option value="">Tous</option>
              @foreach (\App\Enums\Depot\StatutVetement::options() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['statut'] ?? null) === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="v-col-md-3 v-col-sm-6 v-col-12">
            <label for="moderation" class="tc-label">Validation</label>
            <select id="moderation" name="moderation" class="tc-control">
              <option value="">Toutes</option>
              @foreach (\App\Enums\Depot\Moderation::options() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['moderation'] ?? null) === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="v-col-12 d-flex align-end gap-2">
            @include('admin.depot.partials.btn', ['label' => 'Filtrer', 'icon' => 'bx-filter', 'variant' => 'tonal'])
            @if (array_filter($filters))
              @include('admin.depot.partials.btn', ['title' => 'Réinitialiser les filtres', 'icon' => 'bx-reset', 'iconOnly' => true, 'variant' => 'text', 'color' => 'secondary', 'href' => route('admin.depot.vetements.index')])
            @endif
          </div>
        </div>
      </form>
    </div>

    <div class="v-table v-theme--light v-table--density-default">
      <div class="v-table__wrapper">
        <table>
          <thead>
            <tr>
              <th class="text-start">Vêtement</th>
              <th class="text-start">Catégorie</th>
              <th class="text-start">Taille</th>
              <th class="text-start">État</th>
              <th class="text-start">Statut</th>
              <th class="text-start">Validation</th>
              <th class="text-start">Déposé le</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($vetements as $vetement)
              <tr>
                <td>
                  <div class="d-flex align-center gap-3">
                    <img src="{{ $vetement->photo_url }}" alt="" class="tc-thumb">
                    <div>
                      <a href="{{ route('admin.depot.vetements.show', $vetement) }}" class="font-weight-medium text-high-emphasis">{{ $vetement->titre }}</a>
                      <div class="text-body-2 text-disabled">{{ $vetement->genre->label() }} · {{ $vetement->matiere }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ $vetement->categorie->nom }}</td>
                <td>{{ $vetement->taille->label() }}</td>
                <td>{{ $vetement->etat->label() }}</td>
                <td>@include('admin.depot.partials.chip', ['label' => $vetement->statut->label(), 'color' => $vetement->statut->color()])</td>
                <td>@include('admin.depot.partials.chip', ['label' => $vetement->moderation->label(), 'color' => $vetement->moderation->color()])</td>
                <td>{{ $vetement->date_depot->format('d/m/Y') }}</td>
                <td class="text-end">
                  @if ($vetement->moderation === \App\Enums\Depot\Moderation::EnAttente)
                    <form method="POST" action="{{ route('admin.depot.vetements.approuver', $vetement) }}" class="d-inline">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="retour" value="liste">
                      @include('admin.depot.partials.btn', ['title' => 'Approuver et publier', 'icon' => 'bx-check-circle', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'success'])
                    </form>
                  @endif
                  @include('admin.depot.partials.btn', ['title' => 'Voir', 'icon' => 'bx-show', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'default', 'href' => route('admin.depot.vetements.show', $vetement)])
                  @include('admin.depot.partials.btn', ['title' => 'Modifier', 'icon' => 'bx-edit-alt', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'default', 'href' => route('admin.depot.vetements.edit', $vetement)])
                  <form method="POST" action="{{ route('admin.depot.vetements.destroy', $vetement) }}" class="d-inline" onsubmit="return confirm(@js("Supprimer « {$vetement->titre} » ?"))">
                    @csrf
                    @method('DELETE')
                    @include('admin.depot.partials.btn', ['title' => 'Supprimer', 'icon' => 'bx-trash', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'error'])
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-disabled py-6">{{ $aValider ? 'Aucun dépôt en attente : tous les dépôts des clients ont été traités.' : 'Aucun vêtement ne correspond à votre recherche.' }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="v-card-text">
      {{ $vetements->links('admin.depot.partials.pagination') }}
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
