@extends('layouts.back')

@section('title', 'Projets d\'upcycling')

@section('content')
@include('admin.upcycling.partials.styles')
<div class="d-flex flex-wrap align-center justify-space-between gap-4 mb-6">
  <div>
    <h4 class="text-h4 mb-1">Projets d'upcycling</h4>
    <p class="mb-0 text-body-1">Tutoriels de transformation de vêtements proposés sur la plateforme.</p>
  </div>
  <a href="{{ route('admin.upcycling.projets.create') }}" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated">
    <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
    <span class="v-btn__content"><i class="bx-plus v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Nouveau projet</span>
  </a>
</div>

@include('admin.upcycling.partials.flash')

@php
  $filtresStatut = ['' => 'Tous', 'brouillon' => 'Brouillons', 'soumis' => 'Soumis', 'publie' => 'Publiés', 'rejete' => 'Rejetés'];
@endphp
<nav aria-label="Filtrer les projets par statut" class="d-flex flex-wrap gap-2 mb-4">
  @foreach ($filtresStatut as $valeur => $libelle)
    <a href="{{ route('admin.upcycling.projets.index', array_merge(request()->only(['q', 'difficulte']), $valeur === '' ? [] : ['statut' => $valeur])) }}"
      @class(['v-btn v-theme--light v-btn--density-default v-btn--size-small v-btn--variant-outlined', 'bg-primary' => (string) request('statut', '') === $valeur])
      @if ((string) request('statut', '') === $valeur) aria-current="page" @endif>{{ $libelle }}</a>
  @endforeach
</nav>


<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
  {{-- Recherche et filtres --}}
  <div class="v-card-text">
    <form method="GET" action="{{ route('admin.upcycling.projets.index') }}" class="v-row align-end">
      <div class="v-col-md-5 v-col-12">
        <label for="q" class="tcu-label">Rechercher</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Titre, vêtement, résultat ou auteur…"
          @class(['tcu-input', 'is-invalid' => $errors->has('q')])>
        @error('q') <div class="tcu-error">{{ $message }}</div> @enderror
      </div>
      <div class="v-col-md-2 v-col-sm-6 v-col-12">
        <label for="difficulte" class="tcu-label">Difficulté</label>
        <select id="difficulte" name="difficulte" class="tcu-input">
          <option value="">Toutes</option>
          @foreach ($difficultes as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected(request('difficulte') === $valeur)>{{ $libelle }}</option>
          @endforeach
        </select>
      </div>
      <div class="v-col-md-2 v-col-sm-6 v-col-12">
        <label for="statut" class="tcu-label">Statut</label>
        <select id="statut" name="statut" class="tcu-input">
          @foreach ($filtresStatut as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
          @endforeach
        </select>
        @error('statut') <div class="tcu-error" role="alert">{{ $message }}</div> @enderror
      </div>
      <div class="v-col-md-3 v-col-12 d-flex gap-2">
        <button type="submit" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated flex-grow-1">
          <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
          <span class="v-btn__content"><i class="bx-filter-alt v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Filtrer</span>
        </button>
        @if (request()->hasAny(['q', 'difficulte', 'statut']))
          <a href="{{ route('admin.upcycling.projets.index') }}" class="v-btn v-theme--light text-secondary v-btn--density-default v-btn--size-default v-btn--variant-outlined" title="Réinitialiser les filtres">
            <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
            <span class="v-btn__content"><i class="bx-reset v-icon notranslate v-theme--light" aria-hidden="true"></i></span>
          </a>
        @endif
      </div>
    </form>
  </div>

  <div class="v-table v-theme--light v-table--density-default v-table--hover">
    <div class="v-table__wrapper">
      <table>
        <thead>
          <tr>
            <th class="text-start">Titre</th>
            <th class="text-start">Auteur</th>
            <th class="text-start">Difficulté</th>
            <th class="text-start">Durée</th>
            <th class="text-center">Étapes</th>
            <th class="text-start">Statut</th>
            <th class="text-start">Date de création</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($projets as $projet)
            <tr>
              <td>
                <div class="d-flex align-center gap-3 py-2">
                  @if ($projet->photoApresUrl())
                    <img src="{{ $projet->photoApresUrl() }}" alt="" class="tcu-thumb">
                  @else
                    <div class="v-avatar v-theme--light text-info v-avatar--density-default v-avatar--variant-tonal rounded flex-shrink-0" style="width: 56px; height: 56px;">
                      <i class="bx-palette v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 26px;"></i>
                      <span class="v-avatar__underlay"></span>
                    </div>
                  @endif
                  <div>
                    <a href="{{ route('admin.upcycling.projets.show', $projet) }}" class="font-weight-medium text-high-emphasis">{{ $projet->titre }}</a>
                    <div class="text-body-2 text-disabled">{{ $projet->vetement_origine }} → {{ $projet->resultat }}</div>
                  </div>
                </div>
              </td>
              <td>{{ $projet->user->name }}</td>
              <td>@include('admin.upcycling.partials.chip', ['enum' => $projet->difficulte])</td>
              <td class="text-no-wrap">{{ $projet->dureeFormatee() }}</td>
              <td class="text-center">{{ $projet->etapes_count }}</td>
              <td>@include('admin.upcycling.partials.chip', ['enum' => $projet->statut])</td>
              <td class="text-no-wrap"><time datetime="{{ $projet->created_at?->toIso8601String() }}">{{ $projet->created_at?->format('d/m/Y H:i') ?? '—' }}</time></td>
              <td class="text-end text-no-wrap">
                <a href="{{ route('admin.upcycling.projets.show', $projet) }}" class="v-btn v-theme--light text-default v-btn--density-default v-btn--size-small v-btn--variant-text" title="Voir et gérer les étapes">
                  <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
                  <span class="v-btn__content"><i class="bx-show v-icon notranslate v-theme--light" aria-hidden="true"></i> Voir</span>
                </a>
                @if ($projet->statut === \App\Enums\Upcycling\StatutProjet::Soumis)
                  @can('moderate', $projet)
                    <form method="POST" action="{{ route('admin.upcycling.projets.publier', $projet) }}" class="tcu-inline-form" data-upcycling-publish="{{ $projet->id }}"
                      onsubmit="return confirm('Confirmer la publication de ce projet dans la galerie publique ?')">
                      @csrf
                      <button type="submit" class="v-btn v-theme--light bg-success v-btn--density-default v-btn--size-small v-btn--variant-tonal">Publier</button>
                    </form>
                    @php($refusActif = (string) old('moderation_projet_id') === (string) $projet->id)
                    <details class="tcu-rejection" data-upcycling-reject="{{ $projet->id }}" @if ($refusActif && $errors->has('commentaire_refus')) open @endif>
                      <summary class="v-btn v-theme--light text-error v-btn--density-default v-btn--size-small v-btn--variant-outlined">Rejeter</summary>
                      <form method="POST" action="{{ route('admin.upcycling.projets.rejeter', $projet) }}" class="tcu-rejection-form">
                        @csrf
                        <input type="hidden" name="moderation_projet_id" value="{{ $projet->id }}">
                        <label class="tcu-label" for="commentaire-refus-{{ $projet->id }}">Commentaire de refus (obligatoire)</label>
                        <textarea id="commentaire-refus-{{ $projet->id }}" name="commentaire_refus" required maxlength="2000" class="tcu-input" rows="3"
                          aria-invalid="{{ $refusActif && $errors->has('commentaire_refus') ? 'true' : 'false' }}">{{ $refusActif ? old('commentaire_refus') : '' }}</textarea>
                        @if ($refusActif)
                          @error('commentaire_refus') <div class="tcu-error" role="alert">{{ $message }}</div> @enderror
                        @endif
                        <button type="submit" class="v-btn v-theme--light text-error v-btn--density-default v-btn--size-small v-btn--variant-outlined mt-2">Confirmer le refus</button>
                      </form>
                    </details>
                  @endcan
                @endif
                <a href="{{ route('admin.upcycling.projets.edit', $projet) }}" class="v-btn v-btn--icon v-theme--light text-default v-btn--density-default v-btn--size-small v-btn--variant-text" title="Modifier">
                  <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
                  <span class="v-btn__content"><i class="bx-edit v-icon notranslate v-theme--light" aria-hidden="true"></i></span>
                </a>
                <form method="POST" action="{{ route('admin.upcycling.projets.destroy', $projet) }}" class="tcu-inline-form"
                  onsubmit="return confirm('Supprimer définitivement ce projet et toutes ses étapes ?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="v-btn v-btn--icon v-theme--light text-error v-btn--density-default v-btn--size-small v-btn--variant-text" title="Supprimer">
                    <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
                    <span class="v-btn__content"><i class="bx-trash v-icon notranslate v-theme--light" aria-hidden="true"></i></span>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-8 text-disabled">
                @if (request()->hasAny(['q', 'difficulte', 'statut']))
                  Aucun projet ne correspond à votre recherche.
                @else
                  Aucun projet pour le moment.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if ($projets->total() > 0)
    <div class="v-card-text d-flex flex-wrap align-center justify-space-between gap-4">
      <span class="text-body-2 text-disabled">{{ $projets->firstItem() }} à {{ $projets->lastItem() }} sur {{ $projets->total() }} projet(s)</span>
      {{ $projets->links('admin.upcycling.partials.pagination') }}
    </div>
  @endif
  <span class="v-card__underlay"></span>
</div>
@endsection
