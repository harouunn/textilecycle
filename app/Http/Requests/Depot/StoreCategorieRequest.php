<?php

namespace App\Http\Requests\Depot;

use App\Http\Requests\Depot\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreCategorieRequest extends FormRequest
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
     * Une case décochée n'est pas envoyée par le navigateur : on la convertit en « false ».
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100', $this->nomUnique()],
            'description' => ['nullable', 'string', 'max:1000'],
            'icone' => ['nullable', 'string', 'max:50'],
            'couleur' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'ordre' => ['required', 'integer', 'min:0', 'max:999'],
            'active' => ['boolean'],
        ];
    }

    protected function nomUnique(): Unique
    {
        return Rule::unique('categories', 'nom');
    }
}
