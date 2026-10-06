@extends('layouts.back')

@section('title', 'Associations')

@include('admin.dons.partials.styles')

@section('content')
<div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
  <div>
    <h4 class="text-h4 mb-1">Associations partenaires</h4>
    <p class="mb-0 text-medium-emphasis">Gérez les associations qui reçoivent les dons de vêtements.</p>
  </div>
  <div class="d-flex gap-2">
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.index'), 'label' => 'Voir les dons', 'variant' => 'tonal', 'icon' => 'bx-donate-heart'])
    @include('admin.dons.partials.btn', ['href' => route('admin.dons.associations.create'), 'label' => 'Nouvelle association', 'icon' => 'bx-plus'])
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
  <div class="v-card-text">
    <form method="GET" action="{{ route('admin.dons.associations.index') }}" class="tcd-filters">
      <div>
        <label for="q" class="tcd-label">Rechercher</label>
        <input type="search" id="q" name="q" value="{{ $search }}" class="tcd-input" placeholder="Nom, ville ou e-mail">
      </div>
      <div>
        <label for="active" class="tcd-label">Statut</label>
        <select id="active" name="active" class="tcd-input">
          <option value="">Toutes</option>
          <option value="1" @selected($active === '1')>Actives</option>
          <option value="0" @selected($active === '0')>Inactives</option>
        </select>
      </div>
      <div class="tcd-filters-actions">
        @include('admin.dons.partials.btn', ['label' => 'Filtrer', 'icon' => 'bx-filter-alt'])
        @if ($search !== '' || $active !== null)
          @include('admin.dons.partials.btn', ['href' => route('admin.dons.associations.index'), 'label' => 'Réinitialiser', 'variant' => 'tonal', 'color' => 'secondary'])
        @endif
      </div>
    </form>
  </div>

  <div class="v-table v-theme--light v-table--density-default tcd-table">
    <div class="v-table__wrapper">
      <table>
        <thead>
          <tr>
            <th>Association</th>
            <th>Ville</th>
            <th>Contact</th>
            <th class="text-center">Nb de dons</th>
            <th class="text-end">Total (kg)</th>
            <th class="text-center">Statut</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($associations as $association)
            <tr>
              <td>
                <div class="d-flex align-center gap-3">
                  @if ($association->logo_url)
                    <img src="{{ $association->logo_url }}" alt="" class="tcd-logo">
                  @else
                    <span class="tcd-logo">{{ mb_strtoupper(mb_substr($association->nom, 0, 1)) }}</span>
                  @endif
                  <a href="{{ route('admin.dons.associations.show', $association) }}" class="font-weight-medium text-high-emphasis">{{ $association->nom }}</a>
                </div>
              </td>
              <td>{{ $association->ville }}</td>
              <td>
                <div class="text-body-2">{{ $association->email }}</div>
                <div class="text-body-2 text-medium-emphasis">{{ $association->telephone }}</div>
              </td>
              <td class="text-center">
                <a href="{{ route('admin.dons.dons.index', ['association_id' => $association->id]) }}">{{ $association->dons_count }}</a>
              </td>
              <td class="text-end tcd-nowrap">{{ number_format((float) $association->dons_sum_poids_kg, 2, ',', ' ') }}</td>
              <td class="text-center">
                <span class="v-chip v-theme--light text-{{ $association->active ? 'success' : 'secondary' }} v-chip--density-default v-chip--size-small v-chip--variant-tonal">
                  <span class="v-chip__underlay"></span>
                  <div class="v-chip__content">{{ $association->active ? 'Active' : 'Inactive' }}</div>
                </span>
              </td>
              <td class="text-end">
                <div class="d-flex justify-end gap-1">
                  @include('admin.dons.partials.btn', ['href' => route('admin.dons.associations.show', $association), 'icon' => 'bx-show', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Voir'])
                  @include('admin.dons.partials.btn', ['href' => route('admin.dons.associations.edit', $association), 'icon' => 'bx-edit', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Modifier'])
                  <form method="POST" action="{{ route('admin.dons.associations.destroy', $association) }}" class="tcd-inline">
                    @csrf
                    @method('DELETE')
                    @include('admin.dons.partials.btn', ['icon' => 'bx-trash', 'variant' => 'text', 'color' => 'error', 'size' => 'small', 'title' => 'Supprimer',
                      'confirm' => "Supprimer « {$association->nom} » et ses {$association->dons_count} don(s) ?"])
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-medium-emphasis py-6">Aucune association trouvée.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="v-card-text">
    {{ $associations->links('admin.dons.partials.pagination') }}
  </div>
  <span class="v-card__underlay"></span>
</div>
@endsection
