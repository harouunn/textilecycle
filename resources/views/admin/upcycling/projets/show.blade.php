@extends('layouts.back')

@section('title', $projet->titre)

@section('content')
@include('admin.upcycling.partials.styles')
<div class="d-flex flex-wrap align-end justify-space-between gap-4 mb-6">
  <div>
    <a href="{{ route('admin.upcycling.projets.index') }}" class="text-body-2"><i class="bx-arrow-back v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Retour aux projets</a>
    <h4 class="text-h4 mt-2 mb-2">{{ $projet->titre }}</h4>
    <div class="d-flex flex-wrap align-center gap-2">
      @include('admin.upcycling.partials.chip', ['enum' => $projet->statut])
      @include('admin.upcycling.partials.chip', ['enum' => $projet->difficulte])
      <span class="text-body-2 ms-2"><i class="bx-time-five v-icon notranslate v-theme--light me-1" aria-hidden="true"></i>{{ $projet->dureeFormatee() }}</span>
      <span class="text-body-2 ms-2"><i class="bx-user v-icon notranslate v-theme--light me-1" aria-hidden="true"></i>{{ $projet->user->name }}</span>
    </div>
  </div>
  <div class="d-flex gap-2">
    @if ($projet->estPublie())
      <a href="{{ route('upcycling.projets.show', $projet) }}" target="_blank" class="v-btn v-theme--light text-secondary v-btn--density-default v-btn--size-default v-btn--variant-outlined">
        <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
        <span class="v-btn__content"><i class="bx-show v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Voir sur le site</span>
      </a>
    @endif
    <a href="{{ route('admin.upcycling.projets.edit', $projet) }}" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated">
      <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
      <span class="v-btn__content"><i class="bx-edit v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Modifier</span>
    </a>
    <form method="POST" action="{{ route('admin.upcycling.projets.destroy', $projet) }}" class="tcu-inline-form"
      onsubmit="return confirm('Supprimer définitivement ce projet et toutes ses étapes ?')">
      @csrf
      @method('DELETE')
      <button type="submit" class="v-btn v-theme--light text-error v-btn--density-default v-btn--size-default v-btn--variant-tonal">
        <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
        <span class="v-btn__content"><i class="bx-trash v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Supprimer</span>
      </button>
    </form>
  </div>
</div>

@include('admin.upcycling.partials.flash')

<div class="v-row">
  {{-- Détails --}}
  <div class="v-col-lg-7 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item">
        <div class="v-card-item__content"><div class="v-card-title">Description</div></div>
      </div>
      <div class="v-card-text">
        <p class="tcu-pre">{{ $projet->description }}</p>
        <div class="v-row mt-2">
          <div class="v-col-sm-6 v-col-12">
            <h6 class="text-h6 mb-1">Vêtement d'origine</h6>
            <p class="mb-0">{{ $projet->vetement_origine }}</p>
          </div>
          <div class="v-col-sm-6 v-col-12">
            <h6 class="text-h6 mb-1">Résultat</h6>
            <p class="mb-0">{{ $projet->resultat }}</p>
          </div>
          <div class="v-col-12">
            <h6 class="text-h6 mb-1">Matériel nécessaire</h6>
            <p class="mb-0 tcu-pre">{{ $projet->materiel_necessaire }}</p>
          </div>
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  {{-- Photos avant / après --}}
  <div class="v-col-lg-5 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item">
        <div class="v-card-item__content"><div class="v-card-title">Avant / après</div></div>
      </div>
      <div class="v-card-text">
        <div class="v-row">
          @foreach (['Avant' => $projet->photoAvantUrl(), 'Après' => $projet->photoApresUrl()] as $libelle => $url)
            <div class="v-col-6">
              <p class="text-body-2 font-weight-medium mb-2">{{ $libelle }}</p>
              @if ($url)
                <img src="{{ $url }}" alt="Photo {{ mb_strtolower($libelle) }}" class="tcu-photo">
              @else
                <div class="tcu-photo-vide"><i class="bx-image v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 32px;"></i></div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  {{-- Étapes --}}
  <div class="v-col-12" id="etapes">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Étapes ({{ $projet->etapes->count() }})</div>
          <div class="v-card-subtitle">Affichées dans l'ordre de leur numéro</div>
        </div>
      </div>

      @forelse ($projet->etapes as $etape)
        <div class="v-card-text tcu-etape d-flex gap-4 align-start">
          <div class="v-avatar v-theme--light text-primary v-avatar--density-default v-avatar--variant-tonal rounded flex-shrink-0" style="width: 38px; height: 38px;">
            <span class="font-weight-medium">{{ $etape->numero }}</span>
            <span class="v-avatar__underlay"></span>
          </div>
          <div class="flex-grow-1">
            <h6 class="text-h6 mb-1">{{ $etape->titre }}</h6>
            <p class="mb-0 tcu-pre">{{ $etape->contenu }}</p>
          </div>
          @if ($etape->photoUrl())
            <img src="{{ $etape->photoUrl() }}" alt="Photo de l'étape {{ $etape->numero }}" class="tcu-thumb flex-shrink-0">
          @endif
          <div class="d-flex flex-shrink-0">
            <a href="{{ route('admin.upcycling.projets.etapes.edit', [$projet, $etape]) }}" class="v-btn v-btn--icon v-theme--light text-default v-btn--density-default v-btn--size-small v-btn--variant-text" title="Modifier l'étape">
              <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
              <span class="v-btn__content"><i class="bx-edit v-icon notranslate v-theme--light" aria-hidden="true"></i></span>
            </a>
            <form method="POST" action="{{ route('admin.upcycling.projets.etapes.destroy', [$projet, $etape]) }}" class="tcu-inline-form"
              onsubmit="return confirm('Supprimer cette étape ?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="v-btn v-btn--icon v-theme--light text-error v-btn--density-default v-btn--size-small v-btn--variant-text" title="Supprimer l'étape">
                <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
                <span class="v-btn__content"><i class="bx-trash v-icon notranslate v-theme--light" aria-hidden="true"></i></span>
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="v-card-text text-disabled">Aucune étape pour le moment. Ajoutez la première ci-dessous.</div>
      @endforelse

      <hr class="v-divider v-theme--light">

      {{-- Ajout d'une étape --}}
      <div class="v-card-text">
        <h6 class="text-h6 mb-4">Ajouter une étape</h6>
        <form method="POST" action="{{ route('admin.upcycling.projets.etapes.store', $projet) }}" enctype="multipart/form-data" data-upcycling-form>
          @csrf
          @include('admin.upcycling.etapes._form', ['etape' => null, 'numeroParDefaut' => $prochainNumero])
          <button type="submit" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated mt-4">
            <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
            <span class="v-btn__content"><i class="bx-plus v-icon notranslate v-theme--light me-1" aria-hidden="true"></i> Ajouter l'étape</span>
          </button>
        </form>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
