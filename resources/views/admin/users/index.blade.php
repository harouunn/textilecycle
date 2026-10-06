@extends('layouts.back')

@section('title', 'Utilisateurs')

@include('admin.dons.partials.styles')

@section('content')
<div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
  <div>
    <h4 class="text-h4 mb-1">Utilisateurs</h4>
    <p class="mb-0 text-medium-emphasis">Gérez les comptes inscrits sur TexTileCycle.</p>
  </div>
  @include('admin.dons.partials.btn', ['href' => route('admin.users.create'), 'label' => 'Nouvel utilisateur', 'icon' => 'bx-plus'])
</div>

@include('admin.dons.partials.flash')

<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
  <div class="v-card-text">
    <form method="GET" action="{{ route('admin.users.index') }}" class="tcd-filters">
      <div>
        <label for="q" class="tcd-label">Rechercher</label>
        <input type="search" id="q" name="q" value="{{ $search }}" class="tcd-input" placeholder="Nom ou e-mail">
      </div>
      <div class="tcd-filters-actions">
        @include('admin.dons.partials.btn', ['label' => 'Rechercher', 'icon' => 'bx-search'])
        @if ($search !== '')
          @include('admin.dons.partials.btn', ['href' => route('admin.users.index'), 'label' => 'Réinitialiser', 'variant' => 'tonal', 'color' => 'secondary'])
        @endif
      </div>
    </form>
  </div>

  <div class="v-table v-theme--light v-table--density-default tcd-table">
    <div class="v-table__wrapper">
      <table>
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Inscrit le</th>
            <th class="text-center">Dons</th>
            <th class="text-end">Poids donné</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            <tr>
              <td>
                <div class="d-flex align-center gap-3">
                  <span class="tcd-logo">{{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}</span>
                  <div>
                    <a href="{{ route('admin.users.show', $user) }}" class="font-weight-medium">{{ $user->name }}</a>
                    @if ($user->is(auth()->user()))
                      <span class="text-body-2 text-medium-emphasis">(vous)</span>
                    @endif
                    <div class="text-body-2 text-medium-emphasis">{{ $user->email }}</div>
                  </div>
                </div>
              </td>
              <td class="tcd-nowrap">{{ $user->created_at?->format('d/m/Y') }}</td>
              <td class="text-center">
                @if ($user->dons_count)
                  <a href="{{ route('admin.dons.dons.index', ['q' => $user->email]) }}">{{ $user->dons_count }}</a>
                @else
                  0
                @endif
              </td>
              <td class="text-end tcd-nowrap">{{ number_format((float) $user->dons_sum_poids_kg, 2, ',', ' ') }} kg</td>
              <td class="text-end">
                <div class="d-flex justify-end gap-1">
                  @include('admin.dons.partials.btn', ['href' => route('admin.users.show', $user), 'icon' => 'bx-show', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Voir'])
                  @include('admin.dons.partials.btn', ['href' => route('admin.users.edit', $user), 'icon' => 'bx-edit', 'variant' => 'text', 'color' => 'secondary', 'size' => 'small', 'title' => 'Modifier'])
                  @unless ($user->is(auth()->user()))
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="tcd-inline">
                      @csrf
                      @method('DELETE')
                      @include('admin.dons.partials.btn', ['icon' => 'bx-trash', 'variant' => 'text', 'color' => 'error', 'size' => 'small', 'title' => 'Supprimer',
                        'confirm' => "Supprimer le compte de {$user->name} et ses {$user->dons_count} don(s) ?"])
                    </form>
                  @endunless
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-medium-emphasis py-6">Aucun utilisateur trouvé.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="v-card-text">
    {{ $users->links('admin.dons.partials.pagination') }}
  </div>
  <span class="v-card__underlay"></span>
</div>
@endsection
