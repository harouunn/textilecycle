<?php

namespace App\Rules;

use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class UpcyclingImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Le fichier doit être une image JPEG, PNG ou WebP valide.');

            return;
        }
        $path = $value->getRealPath();
        $dimensions = @getimagesize($path);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $types = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp'];
        if (! $dimensions || ! isset($types[$dimensions[2]]) || $types[$dimensions[2]] !== $mime) {
            $fail('Le fichier doit être une image JPEG, PNG ou WebP valide.');
        }
    }
}
