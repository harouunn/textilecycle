@extends('layouts.back')

@section('title', 'Demandes de réparation')

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
    <div class="v-card-item">
      <div class="v-card-item__content">
        <div class="v-card-title">Demandes de réparation</div>
        <div class="v-card-subtitle">{{ $demandes->total() }} demande(s)</div>
      </div>
      <div class="v-card-item__append">
        @include('admin.depot.partials.btn', ['label' => 'Nouvelle demande', 'icon' => 'bx-plus', 'href' => route('admin.ateliers.demandes.create')])
      </div>
    </div>

    {{-- Recherche et filtres --}}
    <div class="v-card-text">
      <form method="GET" action="{{ route('admin.ateliers.demandes.index') }}">
        <div class="v-row">
          <div class="v-col-md-4 v-col-sm-6 v-col-12">
            <label for="search" class="tc-label">Titre</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Rechercher par titre…" class="tc-control">
          </div>
          <div class="v-col-md-3 v-col-sm-6 v-col-12">
            <label for="atelier" class="tc-label">Atelier</label>
            <select id="atelier" name="atelier" class="tc-control">
              <option value="">Tous</option>
              @foreach ($ateliers as $id => $nom)
                <option value="{{ $id }}" @selected(($filters['atelier'] ?? null) == $id)>{{ $nom }}</option>
              @endforeach
            </select>
          </div>
          <div class="v-col-md-3 v-col-sm-6 v-col-12">
            <label for="statut" class="tc-label">Statut</label>
            <select id="statut" name="statut" class="tc-control">
              <option value="">Tous</option>
              @foreach (\App\Enums\Ateliers\StatutDemande::options() as $value => $label)
                <option value="{{ $value }}" @selected(($filters['statut'] ?? null) === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="v-col-md-2 v-col-12 d-flex align-end gap-2">
            @include('admin.depot.partials.btn', ['label' => 'Filtrer', 'icon' => 'bx-filter', 'variant' => 'tonal'])
            @if (array_filter($filters))
              @include('admin.depot.partials.btn', ['title' => 'Réinitialiser les filtres', 'icon' => 'bx-reset', 'iconOnly' => true, 'variant' => 'text', 'color' => 'secondary', 'href' => route('admin.ateliers.demandes.index')])
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
              <th class="text-start">Demande</th>
              <th class="text-start">Atelier</th>
              <th class="text-start">Client</th>
              <th class="text-end">Coût</th>
              <th class="text-end">Délai</th>
              <th class="text-start">Statut</th>
              <th class="text-start">Reçue le</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($demandes as $demande)
              <tr>
                <td>
                  <div class="d-flex align-center gap-3">
                    @if ($demande->photo)
                      <img src="{{ $demande->photo_url }}" alt="" class="tc-thumb">
                    @endif
                    <div>
                      <a href="{{ route('admin.ateliers.demandes.show', $demande) }}" class="font-weight-medium text-high-emphasis">{{ $demande->titre }}</a>
                      <div class="text-body-2 text-disabled">{{ $demande->reparations->map->label()->join(', ') }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ $demande->atelier->nom }}</td>
                <td>{{ $demande->user->name }}</td>
                <td class="text-end">{{ $demande->cout_affiche }}</td>
                <td class="text-end">{{ $demande->delai_estime_jours }} j</td>
                <td>@include('admin.depot.partials.chip', ['label' => $demande->statut->label(), 'color' => $demande->statut->color()])</td>
                <td>{{ $demande->created_at->format('d/m/Y') }}</td>
                <td class="text-end">
                  @include('admin.depot.partials.btn', ['title' => 'Voir et traiter', 'icon' => 'bx-show', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'default', 'href' => route('admin.ateliers.demandes.show', $demande)])
                  @include('admin.depot.partials.btn', ['title' => 'Modifier', 'icon' => 'bx-edit-alt', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'default', 'href' => route('admin.ateliers.demandes.edit', $demande)])
                  <form method="POST" action="{{ route('admin.ateliers.demandes.destroy', $demande) }}" class="d-inline" onsubmit="return confirm(@js("Supprimer la demande « {$demande->titre} » ?"))">
                    @csrf
                    @method('DELETE')
                    @include('admin.depot.partials.btn', ['title' => 'Supprimer', 'icon' => 'bx-trash', 'iconOnly' => true, 'variant' => 'text', 'size' => 'small', 'color' => 'error'])
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-disabled py-6">Aucune demande ne correspond à votre recherche.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="v-card-text">
      {{ $demandes->links('admin.depot.partials.pagination') }}
    </div>
    <span class="v-card__underlay"></span>
  </div>
@endsection
