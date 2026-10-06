<?php

namespace App\Http\Requests\Depot;

use App\Http\Requests\Depot\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategorieRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:100', Rule::unique('categories', 'nom')->ignore($this->route('categorie'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'icone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
