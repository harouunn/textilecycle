<?php

namespace App\Http\Requests\Dons;

use App\Models\Don;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Back office: change only the statut of a donation.
 */
class UpdateDonStatutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::in(array_keys(Don::STATUTS))],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }
}
