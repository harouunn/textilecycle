@php
  $menu = [
    ['title' => 'Tableau de bord', 'icon' => 'bx-home-smile', 'route' => 'admin.dashboard'],

    ['section' => 'Cycle des vêtements'],
    ['title' => 'Dépôts', 'icon' => 'bx-package'],
    ['title' => 'Réparations', 'icon' => 'bx-wrench'],
    ['title' => 'Upcycling', 'icon' => 'bx-palette'],
    ['title' => 'Dons', 'icon' => 'bx-donate-heart'],

    ['section' => 'Partenaires'],
    ['title' => 'Associations', 'icon' => 'bx-group'],
    ['title' => 'Collecte', 'icon' => 'bx-map'],

    ['section' => 'Administration'],
    ['title' => 'Utilisateurs', 'icon' => 'bx-user'],
    ['title' => 'Statistiques', 'icon' => 'bx-bar-chart-alt-2'],

    ['section' => 'Raccourcis'],
    ['title' => 'Mon profil', 'icon' => 'bx-cog', 'route' => 'profile.edit'],
    ['title' => 'Voir le site', 'icon' => 'bx-show', 'route' => 'home'],
  ];
@endphp

<aside data-v-0433a762 class="layout-vertical-nav" data-nav>
  <div data-v-0433a762 class="nav-header">
    <a data-v-fba8a720 href="{{ route('admin.dashboard') }}" class="app-logo app-title-wrapper">
      <div data-v-fba8a720 class="d-flex text-primary">
        <i class="bx-recycle v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 30px; height: 30px; width: 30px;"></i>
      </div>
      <h1 data-v-fba8a720 class="app-logo-title">TexTileCycle</h1>
    </a>

    <button data-v-fba8a720 type="button" class="v-btn v-btn--icon v-theme--light text-default v-btn--density-default v-btn--size-default v-btn--variant-text d-block d-lg-none" data-nav-close aria-label="Fermer le menu">
      <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
      <span class="v-btn__content"><i class="bx-x v-icon notranslate v-theme--light v-icon--size-default" aria-hidden="true"></i></span>
    </button>
  </div>

  <div data-v-0433a762 class="vertical-nav-items-shadow"></div>

  <ul data-v-0433a762 class="nav-items" data-nav-items>
    @foreach ($menu as $item)
      @if (isset($item['section']))
        <li class="nav-section-title">
          <div class="title-wrapper"><span class="title-text">{{ $item['section'] }}</span></div>
        </li>
      @else
        @php($isActive = isset($item['route']) && request()->routeIs($item['route']))
        <li class="nav-link">
          <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}" @class(['router-link-active router-link-exact-active' => $isActive]) @if ($isActive) aria-current="page" @endif>
            <i class="{{ $item['icon'] }} v-icon notranslate v-theme--light v-icon--size-default nav-item-icon" aria-hidden="true"></i>
            <span class="nav-item-title">{{ $item['title'] }}</span>
            @unless (isset($item['route']))
              <span class="nav-item-badge bg-light-primary text-primary">Bientôt</span>
            @endunless
          </a>
        </li>
      @endif
    @endforeach
  </ul>
</aside>
