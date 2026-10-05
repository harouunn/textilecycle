@extends('layouts.back')

@section('title', $association->nom)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.associations.index') }}" class="text-body-2">‹ Retour aux associations</a>
  <div class="d-flex flex-wrap align-center justify-space-between gap-4 mt-2">
    <div class="d-flex align-center gap-4">
      @if ($association->logo_url)
        <img src="{{ $association->logo_url }}" alt="" class="tcd-logo tcd-logo-lg">
      @else
        <span class="tcd-logo tcd-logo-lg">{{ mb_strtoupper(mb_substr($association->nom, 0, 1)) }}</span>
      @endif
      <div>
        <h4 class="text-h4 mb-1">{{ $association->nom }}</h4>
        <span class="v-chip v-theme--light text-{{ $association->active ? 'success' : 'secondary' }} v-chip--density-default v-chip--size-small v-chip--variant-tonal">
          <span class="v-chip__underlay"></span>
          <div class="v-chip__content">{{ $association->active ? 'Active' : 'Inactive' }}</div>
        </span>
      </div>
    </div>
    <div class="d-flex gap-2">
      @if ($association->active)
        @include('admin.dons.partials.btn', ['href' => route('dons.associations.show', $association), 'label' => 'Page publique', 'variant' => 'tonal', 'color' => 'secondary', 'icon' => 'bx-show'])
      @endif
      @include('admin.dons.partials.btn', ['href' => route('admin.dons.associations.edit', $association), 'label' => 'Modifier', 'icon' => 'bx-edit'])
      <form method="POST" action="{{ route('admin.dons.associations.destroy', $association) }}" class="tcd-inline">
        @csrf
        @method('DELETE')
        @include('admin.dons.partials.btn', ['label' => 'Supprimer', 'icon' => 'bx-trash', 'color' => 'error', 'variant' => 'tonal',
          'confirm' => "Supprimer « {$association->nom} » et ses {$association->dons_count} don(s) ?"])
      </form>
    </div>
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-row">
  <div class="v-col-md-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
      <div class="v-card-text d-flex gap-6">
        <div>
          <p class="mb-1 text-medium-emphasis">Dons</p>
          <h5 class="text-h5 mb-0">{{ $association->dons_count }}</h5>
        </div>
        <div>
          <p class="mb-1 text-medium-emphasis">Poids total</p>
          <h5 class="text-h5 mb-0">{{ number_format((float) $association->dons_sum_poids_kg, 2, ',', ' ') }} kg</h5>
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>

    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Coordonnées</div></div></div>
      <div class="v-card-text">
        <dl class="tcd-dl">
          <dt>Adresse</dt><dd>{{ $association->adresse }}, {{ $association->ville }}</dd>
          <dt>Téléphone</dt><dd>{{ $association->telephone }}</dd>
          <dt>E-mail</dt><dd><a href="mailto:{{ $association->email }}">{{ $association->email }}</a></dd>
          <dt>Site web</dt>
          <dd>
            @if ($association->site_web)
              <a href="{{ $association->site_web }}" target="_blank" rel="noopener">{{ $association->site_web }}</a>
            @else
              <span class="text-medium-emphasis">—</span>
            @endif
          </dd>
          <dt>Créée le</dt><dd>{{ $association->created_at->format('d/m/Y') }}</dd>
        </dl>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  <div class="v-col-md-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Présentation</div></div></div>
      <div class="v-card-text tcd-pre">{{ $association->description }}</div>
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Besoins</div></div></div>
      <div class="v-card-text tcd-pre">{{ $association->besoins }}</div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  <div class="v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item">
        <div class="v-card-item__content d-flex flex-wrap align-center justify-space-between gap-2">
          <div class="v-card-title">Dons reçus par cette association</div>
          @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.create', ['association_id' => $association->id]), 'label' => 'Ajouter un don', 'icon' => 'bx-plus', 'size' => 'small'])
        </div>
      </div>
      <div class="v-table v-theme--light v-table--density-default tcd-table">
        <div class="v-table__wrapper">
          <table>
            <thead>
              <tr><th>N°</th><th>Donateur</th><th>Article</th><th class="text-center">Qté</th><th class="text-end">Poids</th><th>Remise</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
              @forelse ($dons as $don)
                <tr>
                  <td>#{{ $don->id }}</td>
                  <td>{{ $don->user->name }}</td>
                  <td>{{ $don->typeLabel() }}</td>
                  <td class="text-center">{{ $don->quantite }}</td>
                  <td class="text-end tcd-nowrap">{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</td>
                  <td>{{ $don->date_remise->format('d/m/Y') }}</td>
                  <td>@include('admin.dons.partials.statut', ['don' => $don])</td>
                  <td class="text-end">
                    @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.show', $don), 'icon' => 'bx-show', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Voir'])
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-medium-emphasis py-6">Aucun don pour le moment.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="v-card-text">{{ $dons->links('admin.dons.partials.pagination') }}</div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
