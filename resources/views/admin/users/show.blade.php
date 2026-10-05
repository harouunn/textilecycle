@extends('layouts.back')

@section('title', $user->name)

@include('admin.dons.partials.styles')

@section('content')
@php($isSelf = $user->is(auth()->user()))

<div class="mb-6">
  <a href="{{ route('admin.users.index') }}" class="text-body-2">‹ Retour aux utilisateurs</a>
  <div class="d-flex flex-wrap align-center justify-space-between gap-4 mt-2">
    <div class="d-flex align-center gap-4">
      <span class="tcd-logo tcd-logo-lg">{{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}</span>
      <div>
        <h4 class="text-h4 mb-1">{{ $user->name }} @if ($isSelf)<span class="text-body-1 text-medium-emphasis">(vous)</span>@endif</h4>
        <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
      </div>
    </div>
    <div class="d-flex gap-2">
      @include('admin.dons.partials.btn', ['href' => route('admin.users.edit', $user), 'label' => 'Modifier', 'icon' => 'bx-edit'])
      @unless ($isSelf)
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="tcd-inline">
          @csrf
          @method('DELETE')
          @include('admin.dons.partials.btn', ['label' => 'Supprimer', 'icon' => 'bx-trash', 'color' => 'error', 'variant' => 'tonal',
            'confirm' => "Supprimer le compte de {$user->name} et ses {$stats->total} don(s) ?"])
        </form>
      @endunless
    </div>
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-row">
  @foreach ([
    ['Dons proposés', $stats->total, 'bx-donate-heart', 'primary'],
    ['Articles donnés', $stats->articles, 'bx-closet', 'info'],
    ['Poids donné', number_format((float) $stats->poids, 2, ',', ' ').' kg', 'bx-package', 'success'],
    ['Inscrit le', $user->created_at?->format('d/m/Y') ?? '—', 'bx-calendar', 'secondary'],
  ] as [$tileLabel, $tileValue, $tileIcon, $tileColor])
    <div class="v-col-sm-6 v-col-lg-3 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-text d-flex align-center gap-4">
          <div class="v-avatar v-theme--light text-{{ $tileColor }} v-avatar--density-default v-avatar--variant-tonal rounded" style="width: 42px; height: 42px;">
            <i class="{{ $tileIcon }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 24px; height: 24px; width: 24px;"></i>
            <span class="v-avatar__underlay"></span>
          </div>
          <div>
            <p class="mb-0 text-medium-emphasis">{{ $tileLabel }}</p>
            <h5 class="text-h5 mb-0">{{ $tileValue }}</h5>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  @endforeach

  <div class="v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Historique des dons</div></div></div>
      <div class="v-table v-theme--light v-table--density-default tcd-table">
        <div class="v-table__wrapper">
          <table>
            <thead>
              <tr><th>N°</th><th>Association</th><th>Article</th><th class="text-center">Qté</th><th class="text-end">Poids</th><th>Remise</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
              @forelse ($dons as $don)
                <tr>
                  <td>#{{ $don->id }}</td>
                  <td><a href="{{ route('admin.dons.associations.show', $don->association) }}">{{ $don->association->nom }}</a></td>
                  <td>{{ $don->typeLabel() }}</td>
                  <td class="text-center">{{ $don->quantite }}</td>
                  <td class="text-end tcd-nowrap">{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</td>
                  <td class="tcd-nowrap">{{ $don->date_remise->format('d/m/Y') }}</td>
                  <td>@include('admin.dons.partials.statut', ['don' => $don])</td>
                  <td class="text-end">
                    @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.show', $don), 'icon' => 'bx-show', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Voir'])
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-medium-emphasis py-6">Cet utilisateur n'a proposé aucun don.</td></tr>
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
