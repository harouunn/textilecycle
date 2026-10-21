{{-- Module « Associations & dons » : entrée du menu back office (à modifier uniquement par l'équipe du module) --}}
@include('partials.back.nav-link', [
  'title' => 'Tableau de bord dons',
  'icon' => 'bx-bar-chart-alt-2',
  'href' => route('admin.dons.tableau-de-bord'),
  'active' => request()->routeIs('admin.dons.tableau-de-bord'),
])
@include('partials.back.nav-link', [
  'title' => 'Dons aux associations',
  'icon' => 'bx-donate-heart',
  'href' => route('admin.dons.dons.index'),
  'active' => request()->routeIs('admin.dons.dons.*'),
])
@include('partials.back.nav-link', [
  'title' => 'Associations',
  'icon' => 'bx-buildings',
  'href' => route('admin.dons.associations.index'),
  'active' => request()->routeIs('admin.dons.associations.*'),
])
@include('partials.back.nav-link', [
  'title' => 'Campagnes de collecte',
  'icon' => 'bx-target-lock',
  'href' => route('admin.dons.campagnes.index'),
  'active' => request()->routeIs('admin.dons.campagnes.*'),
])
@include('partials.back.nav-link', [
  'title' => 'Journal IA',
  'icon' => 'bx-bulb',
  'href' => route('admin.dons.journal-ia'),
  'active' => request()->routeIs('admin.dons.journal-ia'),
])
