{{-- Module « Upcycling » : entrée du menu back office (à modifier uniquement par l'équipe du module) --}}
@include('partials.back.nav-link', [
  'title' => 'Upcycling',
  'icon' => 'bx-palette',
  'href' => route('admin.upcycling.projets.index'),
  'active' => request()->routeIs('admin.upcycling.*'),
])
