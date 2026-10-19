{{-- Résultat de « Analyser la photo » (front office) --}}
@if (session('analyse'))
  <div class="tc-alerte tc-alerte--info" role="status">
    <x-ui.icone nom="etincelle" />
    <div>
      <strong class="d-block text-uppercase small mb-1" style="letter-spacing: .08em;">Classement automatique de votre photo</strong>
      <ul>
        @foreach (session('analyse') as $indice)
          <li>{{ $indice }}</li>
        @endforeach
      </ul>
      <div class="small mt-2">Les champs ont été pré-remplis : vérifiez-les puis déposez votre vêtement.</div>
    </div>
  </div>
@endif
