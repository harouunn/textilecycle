@extends('layouts.back')

@section('title', 'Tableau de bord')

@section('content')
@php
  $stats = [
    ['label' => 'Vêtements déposés', 'value' => 0, 'icon' => 'bx-package', 'color' => 'primary'],
    ['label' => 'Réparations en cours', 'value' => 0, 'icon' => 'bx-wrench', 'color' => 'warning'],
    ['label' => 'Pièces upcyclées', 'value' => 0, 'icon' => 'bx-palette', 'color' => 'info'],
    ['label' => 'Dons aux associations', 'value' => 0, 'icon' => 'bx-donate-heart', 'color' => 'success'],
  ];

  $steps = [
    ['title' => 'Dépôt', 'text' => 'Les particuliers déposent leurs vêtements en point de collecte ou en ligne.', 'icon' => 'bx-package', 'color' => 'primary'],
    ['title' => 'Réparation', 'text' => 'Les pièces abîmées sont réparées par nos couturiers et partenaires.', 'icon' => 'bx-wrench', 'color' => 'warning'],
    ['title' => 'Upcycling', 'text' => 'Les textiles non réparables sont transformés en nouvelles créations.', 'icon' => 'bx-palette', 'color' => 'info'],
    ['title' => 'Don', 'text' => 'Les vêtements en bon état sont redistribués aux associations partenaires.', 'icon' => 'bx-donate-heart', 'color' => 'success'],
  ];
@endphp

<div class="v-row">
  {{-- Bienvenue --}}
  <div class="v-col-md-8 v-col-12">
    <div data-v-6fa7336e class="v-card v-theme--light v-card--density-default v-card--variant-elevated text-center text-sm-start h-100">
      <div data-v-6fa7336e class="v-row v-row--no-gutters">
        <div data-v-6fa7336e class="v-col-sm-8 order-sm-1 v-col-12 order-2">
          <div data-v-6fa7336e class="v-card-item pb-3">
            <div class="v-card-item__content">
              <div data-v-6fa7336e class="v-card-title text-primary">Bonjour {{ Auth::user()->name }} ! ♻️</div>
            </div>
          </div>
          <div data-v-6fa7336e class="v-card-text">
            Bienvenue dans l'espace d'administration de TexTileCycle.<br>
            Suivez ici les dépôts, réparations, créations upcyclées et dons aux associations.
            <br>
            <a href="{{ route('home') }}" class="v-btn v-theme--light text-primary v-btn--density-default v-btn--size-small v-btn--variant-tonal mt-6">
              <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
              <span class="v-btn__content">Voir le site public</span>
            </a>
          </div>
        </div>
        <div data-v-6fa7336e class="v-col-sm-4 order-sm-2 v-col-12 order-1 text-center">
          <img data-v-6fa7336e src="{{ asset('back/images/cards/illustration-john-light.png') }}" height="175" class="position-absolute tc-welcome-illustration flip-in-rtl" alt="">
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  {{-- Impact --}}
  <div class="v-col-md-4 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated h-100">
      <div class="v-card-item">
        <div class="v-card-item__content"><div class="v-card-title">Impact environnemental</div></div>
      </div>
      <div class="v-card-text">
        <div class="d-flex align-center gap-4">
          <div class="v-avatar v-theme--light text-success v-avatar--density-default v-avatar--variant-tonal rounded" style="width: 48px; height: 48px;">
            <i class="bx-leaf v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 28px; height: 28px; width: 28px;"></i>
            <span class="v-avatar__underlay"></span>
          </div>
          <div>
            <h4 class="text-h4">0 kg</h4>
            <p class="mb-0 text-body-2">de textiles détournés des déchets</p>
          </div>
        </div>
        <p class="mt-4 mb-0 text-body-2 text-disabled">Les indicateurs seront calculés automatiquement dès l'enregistrement des premiers dépôts.</p>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  {{-- Statistiques --}}
  @foreach ($stats as $stat)
    <div class="v-col-sm-6 v-col-lg-3 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-text d-flex align-center pb-4">
          <div class="v-avatar v-theme--light text-{{ $stat['color'] }} v-avatar--density-default v-avatar--variant-tonal rounded" style="width: 42px; height: 42px;">
            <i class="{{ $stat['icon'] }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 24px; height: 24px; width: 24px;"></i>
            <span class="v-avatar__underlay"></span>
          </div>
        </div>
        <div class="v-card-text">
          <p class="mb-1">{{ $stat['label'] }}</p>
          <h5 class="text-h5 text-no-wrap mb-0">{{ $stat['value'] }}</h5>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  @endforeach

  {{-- Le cycle --}}
  <div class="v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title">Le cycle TexTileCycle</div>
          <div class="v-card-subtitle">Les quatre étapes de la seconde vie d'un vêtement</div>
        </div>
      </div>
      <div class="v-card-text">
        <div class="v-row">
          @foreach ($steps as $step)
            <div class="v-col-sm-6 v-col-md-3 v-col-12">
              <div class="d-flex align-start gap-3">
                <div class="v-avatar v-theme--light text-{{ $step['color'] }} v-avatar--density-default v-avatar--variant-tonal rounded flex-shrink-0" style="width: 38px; height: 38px;">
                  <i class="{{ $step['icon'] }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i>
                  <span class="v-avatar__underlay"></span>
                </div>
                <div>
                  <h6 class="text-h6 mb-1">{{ $loop->iteration }}. {{ $step['title'] }}</h6>
                  <p class="mb-0 text-body-2">{{ $step['text'] }}</p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
