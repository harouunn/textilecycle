{{-- Module « Dépôt & vêtements » : entrées du menu back office (à modifier uniquement par l'équipe du module) --}}
@php
  $depotsEnAttente = \App\Models\Vetement::query()->where('moderation', \App\Enums\Depot\Moderation::EnAttente)->count();
  $pageAValider = request()->routeIs('admin.depot.vetements.index') && request('moderation') === \App\Enums\Depot\Moderation::EnAttente->value;
@endphp
@include('partials.back.nav-link', [
  'title' => 'Dépôt & vêtements',
  'icon' => 'bx-package',
  'href' => route('admin.depot.vetements.index'),
  'active' => request()->routeIs('admin.depot.vetements.*') && ! $pageAValider,
])
@include('partials.back.nav-link', [
  'title' => 'Dépôts à valider',
  'icon' => 'bx-check-shield',
  'href' => route('admin.depot.vetements.index', ['moderation' => \App\Enums\Depot\Moderation::EnAttente->value]),
  'active' => $pageAValider,
  'badge' => $depotsEnAttente ?: null,
])
@include('partials.back.nav-link', [
  'title' => 'Catégories',
  'icon' => 'bx-category',
  'href' => route('admin.depot.categories.index'),
  'active' => request()->routeIs('admin.depot.categories.*'),
])
