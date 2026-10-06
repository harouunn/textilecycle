<?php

namespace App\Http\Requests\Upcycling;

use App\Rules\UpcyclingImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

abstract class UpcyclingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Conserve les retours à la ligne du matériel et des consignes.
        $input = $this->input();
        foreach ($input as $field => $value) {
            if (is_string($value)) {
                $input[$field] = preg_replace('/\A[\p{Z}\s]+|[\p{Z}\s]+\z/u', '', $value);
            }
        }
        $this->merge($input);
    }

    public static function photoExistante(?Model $model, string $field): bool
    {
        return $model?->exists && is_string($model->{$field})
            && $model->{$field} !== '' && Storage::disk('public')->exists($model->{$field});
    }

    protected function reglesPhoto(?Model $model, string $field): array
    {
        return [
            'bail', Rule::requiredIf(! static::photoExistante($model, $field)), 'nullable',
            'file', new UpcyclingImage, 'image', 'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp', 'max:2048',
        ];
    }

    protected function messagesPhoto(): array
    {
        return [
            'file' => 'Le fichier doit être une image JPEG, PNG ou WebP.',
            'image' => 'Le fichier doit être une image JPEG, PNG ou WebP valide.',
            'mimes' => 'L’image doit être au format JPEG, PNG ou WebP.',
            'mimetypes' => 'L’image doit être au format JPEG, PNG ou WebP.',
            'uploaded' => 'Le téléversement a échoué. Sélectionnez à nouveau une image de 2 Mo maximum.',
        ];
    }
}
