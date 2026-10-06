@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="status">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
  </div>
@endif

@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    Le formulaire contient des erreurs, veuillez les corriger.
  </div>
@endif
