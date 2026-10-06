<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AtelierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Une case décochée n'est pas envoyée par le navigateur : on la convertit en « false ».
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['actif' => $this->boolean('actif')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adresse' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'code_postal' => 'required|string|max:10',
            'telephone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'actif' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de l\'atelier est obligatoire.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'ville.required' => 'La ville est obligatoire.',
            'code_postal.required' => 'Le code postal est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
        ];
    }
}
