@php
    $validationBag = request()->routeIs('*.etapes.*', 'admin.upcycling.projets.show') ? 'etape' : (request()->routeIs('*.projets.create', '*.projets.edit', 'upcycling.create', 'upcycling.edit') ? 'projet' : 'default');
    $validationErrors = $errors->getBag($validationBag);
@endphp
{{-- Messages flash et récapitulatif des erreurs de validation --}}
@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
  </div>
@endif

@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
  </div>
@endif

@if ($validationErrors->any())
  <div class="alert alert-danger" role="alert">
    Le formulaire contient {{ $validationErrors->count() }} erreur(s). Corrigez les champs signalés ci-dessous.
  </div>
@endif
