@extends('layouts.back')

@section('title', 'Catégories')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="tc-page-head">
    <div>
      <h4 class="text-h4">Catégories de vêtements</h4>
      <p>Organisez le catalogue : nom, couleur, ordre d'affichage et visibilité.</p>
    </div>
    @include('admin.depot.partials.btn', ['label' => 'Nouvelle catégorie', 'icon' => 'bx-plus', 'href' => route('admin.depot.categories.create')])
  </div>

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">{{ $categories->total() }} catégorie(s)</div>
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
              <th class="text-center" style="width: 70px;">Ordre</th>
              <th class="text-start">Catégorie</th>
              <th class="text-start">Description</th>
              <th class="text-start">Vêtements</th>
              <th class="text-start">Visibilité</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($categories as $categorie)
              <tr>
                <td class="text-center tc-num text-medium-emphasis">{{ $categorie->ordre }}</td>
                <td>
                  <div class="d-flex align-center gap-3">
                    <div class="v-avatar v-theme--light v-avatar--density-default rounded" style="width: 36px; height: 36px; background: {{ $categorie->couleur }}1f; color: {{ $categorie->couleur }};">
                      <i class="{{ $categorie->icone ?: 'bx-category' }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 20px; height: 20px; width: 20px;"></i>
                    </div>
                    <div>
                      <div class="font-weight-medium">{{ $categorie->nom }}</div>
                      <div class="text-body-2 text-disabled d-flex align-center gap-1"><span class="tc-swatch" style="background: {{ $categorie->couleur }}"></span>{{ $categorie->couleur }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-body-2" style="white-space: normal; max-width: 320px;">{{ Str::limit($categorie->description, 80) ?: '—' }}</td>
                <td>
                  <a href="{{ route('admin.depot.vetements.index', ['categorie' => $categorie->id]) }}" class="text-high-emphasis" title="Voir les vêtements de cette catégorie">
                    <span class="font-weight-medium tc-num">{{ $categorie->vetements_count }}</span>
                  </a>
                  <div class="text-body-2 text-medium-emphasis">{{ $categorie->disponibles_count }} en ligne</div>
                </td>
                <td>
                  @include('admin.depot.partials.chip', ['label' => $categorie->active ? 'Active' : 'Masquée', 'color' => $categorie->active ? 'success' : 'secondary'])
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
                <td colspan="6" class="text-center text-disabled py-6">Aucune catégorie trouvée.</td>
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
