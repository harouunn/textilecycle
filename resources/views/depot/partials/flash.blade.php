{{-- Messages de confirmation et d'erreur du module (front office) --}}
@if (session('success'))
  <div class="tc-alerte tc-alerte--succes" role="status">
    <x-ui.icone nom="valide" />
    <div>{{ session('success') }}</div>
  </div>
@endif

@if (session('erreur'))
  <div class="tc-alerte tc-alerte--erreur" role="alert">
    <x-ui.icone nom="alerte" />
    <div>{{ session('erreur') }}</div>
  </div>
@endif

@if ($errors->any())
  <div class="tc-alerte tc-alerte--erreur" role="alert">
    <x-ui.icone nom="alerte" />
    <div>
      Le formulaire contient des erreurs, veuillez les corriger.
      @if ($errors->hasAny(['vetement', 'statut']))
        <ul>
          @foreach (array_merge($errors->get('vetement'), $errors->get('statut')) as $message)
            <li>{{ $message }}</li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
@endif
