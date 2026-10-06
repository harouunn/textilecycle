<?php

namespace App\Http\Requests\Ateliers;

use App\Enums\Ateliers\StatutDemande;
use App\Http\Requests\Ateliers\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Réponse de l'atelier à une demande : statut, coût final, date prévue et message au client.
 */
class TraiterDemandeReparationRequest extends FormRequest
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
            'statut' => ['required', Rule::enum(StatutDemande::class)],
            'cout_final' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_prevue' => ['nullable', 'date'],
            'reponse_atelier' => ['nullable', 'required_if:statut,'.StatutDemande::Refusee->value, 'string', 'max:2000'],
        ];
    }
}
