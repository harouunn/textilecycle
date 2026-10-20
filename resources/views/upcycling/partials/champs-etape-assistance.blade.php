<div class="tc-ai-fields my-3">
<label>Outils nécessaires (facultatif)<textarea name="outils_necessaires" maxlength="2000" class="form-control tcu-input">{{ $formOld('outils_necessaires', ($etape ?? null)?->outils_necessaires) }}</textarea></label>
<label>Durée estimée en minutes (facultatif)<input type="number" name="duree_minutes" min="1" max="1440" class="form-control tcu-input" value="{{ $formOld('duree_minutes', ($etape ?? null)?->duree_minutes) }}"></label>
<label>Conseils de sécurité (facultatif)<textarea name="conseils_securite" maxlength="2000" class="form-control tcu-input">{{ $formOld('conseils_securite', ($etape ?? null)?->conseils_securite) }}</textarea></label>
@foreach (['outils_necessaires','duree_minutes','conseils_securite'] as $champ) @error($champ, 'etape')<p role="alert">{{ $message }}</p>@enderror @endforeach
</div>
