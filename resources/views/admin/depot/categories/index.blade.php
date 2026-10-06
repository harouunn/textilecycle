@extends('layouts.back')

@section('title', 'Catégories')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">Catégories de vêtements</div>
        <div class="v-card-subtitle">{{ $categories->total() }} catégorie(s)</div>
      </div>
      <div class="v-card-item__append">
        @include('admin.depot.partials.btn', ['label' => 'Nouvelle catégorie', 'icon' => 'bx-plus', 'href' => route('admin.depot.categories.create')])
      </div>
    </div>

    <div class="v-card-text">
      <form method="GET" action="{{ route('admin.depot.categories.index') }}" class="d-flex flex-wrap gap-4 align-center">
        <input type="search" name="search" value="{{ $search }}" placeholder="Rechercher une catégorie…" class="tc-control" style="max-width: 320px;" aria-label="Rechercher une catégorie">
        @include('admin.depot.partials.btn', ['label' => 'Rechercher', 'icon' => 'bx-search', 'variant' => 'tonal'])
        @if ($search)
          @include('admin.depot.partials.btn', ['label' => 'Réinitialiser', 'icon' => 'bx-reset', 'variant' => 'text', 'color' => 'secondary', 'href' => route('admin.depot.categories.index')])
        @endif
      </form>
    </div>

    <div class="v-table v-theme--light v-table--density-default">
      <div class="v-table__wrapper">
        <table>
          <thead>
            <tr>
              <th class="text-start">Catégorie</th>
              <th class="text-start">Description</th>
              <th class="text-center">Vêtements</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($categories as $categorie)
              <tr>
                <td>
                  <div class="d-flex align-center gap-3">
                    <div class="v-avatar v-theme--light text-primary v-avatar--density-default v-avatar--variant-tonal rounded" style="width: 34px; height: 34px;">
                      <i class="{{ $categorie->icone ?: 'bx-category' }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 20px; height: 20px; width: 20px;"></i>
                      <span class="v-avatar__underlay"></span>
                    </div>
                    <span class="font-weight-medium">{{ $categorie->nom }}</span>
                  </div>
                </td>
                <td class="text-body-2" style="white-space: normal;">{{ Str::limit($categorie->description, 70) ?: '—' }}</td>
                <td class="text-center">
                  <a href="{{ route('admin.depot.vetements.index', ['categorie' => $categorie->id]) }}" title="Voir les vêtements de cette catégorie">
                    @include('admin.depot.partials.chip', ['label' => $categorie->vetements_count, 'color' => $categorie->vetements_count ? 'primary' : 'secondary'])
                  </a>
                </td>
                <td class="text-end">
                  @include('admin.depot.partials.btn', ['title' => 'Modifier', 'icon' => 'bx-edit-alt', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'default', 'href' => route('admin.depot.categories.edit', $categorie)])
                  @php
                    $confirmation = $categorie->vetements_count
                      ? "Supprimer « {$categorie->nom} » et ses {$categorie->vetements_count} vêtement(s) ?"
                      : "Supprimer « {$categorie->nom} » ?";
                  @endphp
                  <form method="POST" action="{{ route('admin.depot.categories.destroy', $categorie) }}" class="d-inline" onsubmit="return confirm(@js($confirmation))">
                    @csrf
                    @method('DELETE')
                    @include('admin.depot.partials.btn', ['title' => 'Supprimer', 'icon' => 'bx-trash', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'error'])
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-disabled py-6">Aucune catégorie trouvée.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="v-card-text">
      {{ $categories->links('admin.depot.partials.pagination') }}
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
