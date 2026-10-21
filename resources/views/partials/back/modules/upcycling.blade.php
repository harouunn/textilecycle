{{-- Module « Upcycling » : entrées du menu back office (à modifier uniquement par l'équipe du module) --}}
@php
  $projetsSoumis = \App\Models\ProjetUpcycling::query()->where('statut', \App\Enums\Upcycling\StatutProjet::Soumis)->count();
  $pageAValider = request()->routeIs('admin.upcycling.projets.index') && request('statut') === \App\Enums\Upcycling\StatutProjet::Soumis->value;
@endphp
@include('partials.back.nav-link', [
  'title' => 'Upcycling',
  'icon' => 'bx-palette',
  'href' => route('admin.upcycling.projets.index'),
  'active' => request()->routeIs('admin.upcycling.*') && ! $pageAValider,
])
@include('partials.back.nav-link', [
  'title' => 'Projets à valider',
  'icon' => 'bx-check-shield',
  'href' => route('admin.upcycling.projets.index', ['statut' => \App\Enums\Upcycling\StatutProjet::Soumis->value]),
  'active' => $pageAValider,
  'badge' => $projetsSoumis ?: null,
])
