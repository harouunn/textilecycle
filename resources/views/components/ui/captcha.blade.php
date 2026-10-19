{{-- Vérification anti-robot : image CAPTCHA à recopier, avec un bouton pour en afficher une autre --}}
@if (app(\App\Services\Auth\Captcha::class)->actif())
  <div class="mb-4">
    <label for="captcha" class="form-label">Vérification : êtes-vous humain ?</label>
    <div class="tc-captcha">
      <img src="{{ route('captcha') }}?t={{ now()->getTimestamp() }}" alt="Code de vérification à recopier" width="200" height="64" id="captcha-image">
      <button type="button" class="tc-oeil position-static" aria-label="Afficher une autre image"
        onclick="document.getElementById('captcha-image').src = '{{ route('captcha') }}?t=' + Date.now(); document.getElementById('captcha').value = ''; document.getElementById('captcha').focus();">
        <x-ui.icone nom="retour" style="transform: rotate(-90deg);" />
      </button>
    </div>
    <input id="captcha" type="text" name="captcha" required maxlength="10" autocomplete="off" autocapitalize="characters" spellcheck="false"
      placeholder="Recopiez les 5 caractères" @class(['form-control mt-2', 'is-invalid' => $errors->has('captcha')])>
    @error('captcha')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
@endif
