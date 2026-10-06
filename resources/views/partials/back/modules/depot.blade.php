{{-- Module « Dépôt & vêtements » : entrées du menu back office (à modifier uniquement par l'équipe du module) --}}
@include('partials.back.nav-link', [
  'title' => 'Dépôt & vêtements',
  'icon' => 'bx-package',
  'href' => route('admin.depot.vetements.index'),
  'active' => request()->routeIs('admin.depot.vetements.*'),
])
@include('partials.back.nav-link', [
  'title' => 'Catégories',
  'icon' => 'bx-category',
  'href' => route('admin.depot.categories.index'),
  'active' => request()->routeIs('admin.depot.categories.*'),
])
