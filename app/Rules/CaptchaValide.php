<?php

namespace App\Rules;

use App\Services\Auth\Captcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Règle de validation : le texte saisi doit correspondre à l'image CAPTCHA affichée.
 */
class CaptchaValide implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(Captcha::class)->verifier(is_string($value) ? $value : null)) {
            $fail('Le code de vérification est incorrect. Recopiez les caractères de la nouvelle image.');
        }
    }
}
