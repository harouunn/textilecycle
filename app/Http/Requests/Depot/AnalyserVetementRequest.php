<?php

namespace App\Http\Requests\Depot;

use App\Http\Requests\Depot\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bouton « Analyser la photo » des formulaires de dépôt : seule la photo est obligatoire,
 * le titre aide à reconnaître le type, la matière et l'état.
 */
class AnalyserVetementRequest extends FormRequest
{
    use MessagesEnFrancais;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,png,webp', 'max:2048'],
            'titre' => ['nullable', 'string', 'max:150'],
        ];
    }
}
