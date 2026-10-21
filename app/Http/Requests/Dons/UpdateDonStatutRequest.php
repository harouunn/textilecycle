<?php

namespace App\Http\Requests\Dons;

use App\Enums\Dons\DonStatut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Change the statut of a donation through the workflow (back office and Espace association).
 * Authorization is checked by DonPolicy::transition in the controller.
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
            'statut' => ['required', Rule::enum(DonStatut::class)],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le statut est obligatoire.',
            'statut.enum' => 'Le statut sélectionné est invalide.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser :max caractères.',
        ];
    }

    public function statut(): DonStatut
    {
        return $this->enum('statut', DonStatut::class);
    }
}
