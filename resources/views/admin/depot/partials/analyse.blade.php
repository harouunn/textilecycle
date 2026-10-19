{{-- Résultat de « Analyser la photo » (back office) --}}
@if (session('analyse'))
  <div class="tc-alert tc-alert--info mb-6" role="status">
    <i class="bx-scan v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i>
    <div>
      <strong>Classement automatique de la photo</strong>
      <ul class="mt-1 mb-1 ps-4">
        @foreach (session('analyse') as $indice)
          <li>{{ $indice }}</li>
        @endforeach
      </ul>
      Les champs vides ont été pré-remplis : vérifiez-les avant d'enregistrer.
    </div>
  </div>
@endif
