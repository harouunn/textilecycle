{{-- Module « Ateliers & réparations » : entrées du menu back office (à modifier uniquement par l'équipe du module) --}}
@include('partials.back.nav-link', [
  'title' => 'Ateliers',
  'icon' => 'bx-store-alt',
  'href' => route('admin.ateliers.index'),
  'active' => request()->routeIs('admin.ateliers.*') && ! request()->routeIs('admin.ateliers.demandes.*'),
])
@include('partials.back.nav-link', [
  'title' => 'Demandes de réparation',
  'icon' => 'bx-wrench',
  'href' => route('admin.ateliers.demandes.index'),
  'active' => request()->routeIs('admin.ateliers.demandes.*'),
])
