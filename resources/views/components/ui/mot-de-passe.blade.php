{{-- Champ mot de passe avec bouton afficher / masquer. Paramètres : id, name, label, autocomplete ; attributs HTML en plus --}}
@props(['id', 'name' => null, 'label', 'autocomplete' => 'current-password'])
@php($name ??= $id)
<div class="mb-3">
  <label for="{{ $id }}" class="form-label">{{ $label }}</label>
  <div class="tc-champ">
    <input id="{{ $id }}" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}"
      {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    <button type="button" class="tc-oeil" data-oeil="{{ $id }}" aria-label="Afficher le mot de passe" aria-pressed="false">
      <span class="tc-oeil-voir"><x-ui.icone nom="oeil" /></span>
      <span class="tc-oeil-cacher" hidden><x-ui.icone nom="oeil-barre" /></span>
    </button>
    @error($name)
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
  {{ $slot }}
</div>
