{{-- Module « Associations & dons » : entrée du menu back office (à modifier uniquement par l'équipe du module) --}}
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
