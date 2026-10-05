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
    @include('partials.back.nav-link', ['title' => 'Tableau de bord', 'icon' => 'bx-home-smile', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')])

    <li class="nav-section-title">
      <div class="title-wrapper"><span class="title-text">Cycle des vêtements</span></div>
    </li>
    {{-- Une entrée par module : chaque équipe ne modifie que son fichier dans partials/back/modules/ --}}
    @include('partials.back.modules.depot')
    @include('partials.back.modules.ateliers')
    @include('partials.back.modules.upcycling')
    @include('partials.back.modules.dons')

    <li class="nav-section-title">
      <div class="title-wrapper"><span class="title-text">Administration</span></div>
    </li>
    @include('partials.back.nav-link', ['title' => 'Utilisateurs', 'icon' => 'bx-user', 'badge' => 'Bientôt'])
    @include('partials.back.nav-link', ['title' => 'Statistiques', 'icon' => 'bx-bar-chart-alt-2', 'badge' => 'Bientôt'])

    <li class="nav-section-title">
      <div class="title-wrapper"><span class="title-text">Raccourcis</span></div>
    </li>
    @include('partials.back.nav-link', ['title' => 'Mon profil', 'icon' => 'bx-cog', 'href' => route('profile.edit'), 'active' => request()->routeIs('profile.edit')])
    @include('partials.back.nav-link', ['title' => 'Voir le site', 'icon' => 'bx-show', 'href' => route('home')])
  </ul>
</aside>
