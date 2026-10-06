{{-- Résultat de « Analyser la photo » (front office) --}}
@if (session('analyse'))
  <div class="alert alert-info" role="status">
    <strong class="d-block mb-2 text-uppercase small">Classement automatique de votre photo</strong>
    <ul class="mb-2 small">
      @foreach (session('analyse') as $indice)
        <li>{{ $indice }}</li>
      @endforeach
    </ul>
    <span class="small">Les champs ont été pré-remplis : vérifiez-les puis déposez votre vêtement.</span>
  </div>
@endif
