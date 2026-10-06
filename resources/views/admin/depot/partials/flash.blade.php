@if (session('success'))
  <div class="tc-alert mb-6" role="status">
    <i class="bx-check-circle v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i>
    {{ session('success') }}
  </div>
@endif

@if ($errors->any())
  <div class="tc-alert tc-alert--error mb-6" role="alert">
    <i class="bx-error-circle v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i>
    Le formulaire contient des erreurs, veuillez les corriger.
  </div>
@endif
