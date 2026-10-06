<?php

namespace App\Http\Requests\Dons;

use App\Models\Don;
use Illuminate\Validation\Rule;

/**
 * Shared validation rules and French messages for a donation.
 */
trait ValidatesDon
{
    /**
     * Rules for the fields filled in by the donor.
     *
     * @param  bool  $checkDate  apply the "today or later" rule to date_remise
     */
    protected function donRules(bool $checkDate = true): array
    {
        return [
            'type_article' => ['required', Rule::in(array_keys(Don::TYPES))],
            'quantite' => ['required', 'integer', 'min:1', 'max:500'],
            'poids_kg' => ['nullable', 'numeric', 'min:0.1', 'max:5000'],
            'etat_general' => ['required', Rule::in(array_keys(Don::ETATS))],
            'mode_remise' => ['required', Rule::in(array_keys(Don::MODES))],
            'date_remise' => $checkDate ? ['required', 'date', 'after_or_equal:today'] : ['required', 'date'],
            'adresse_collecte' => ['nullable', 'required_if:mode_remise,collecte_a_domicile', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'association_id.required' => 'Veuillez choisir une association.',
            'association_id.exists' => 'L\'association sélectionnée est invalide.',
            'user_id.required' => 'Veuillez choisir un donateur.',
            'user_id.exists' => 'Le donateur sélectionné est invalide.',
            'type_article.required' => 'Le type d\'article est obligatoire.',
            'type_article.in' => 'Le type d\'article sélectionné est invalide.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.integer' => 'La quantité doit être un nombre entier.',
            'quantite.min' => 'La quantité doit être d\'au moins :min article.',
            'quantite.max' => 'La quantité ne peut pas dépasser :max articles.',
            'poids_kg.numeric' => 'Le poids doit être un nombre (ex. 2.5).',
            'poids_kg.min' => 'Le poids doit être d\'au moins :min kg.',
            'poids_kg.max' => 'Le poids ne peut pas dépasser :max kg.',
            'etat_general.required' => 'L\'état général est obligatoire.',
            'etat_general.in' => 'L\'état général sélectionné est invalide.',
            'mode_remise.required' => 'Le mode de remise est obligatoire.',
            'mode_remise.in' => 'Le mode de remise sélectionné est invalide.',
            'date_remise.required' => 'La date de remise est obligatoire.',
            'date_remise.date' => 'La date de remise n\'est pas une date valide.',
            'date_remise.after_or_equal' => 'La date de remise doit être aujourd\'hui ou une date future.',
            'adresse_collecte.required_if' => 'L\'adresse de collecte est obligatoire pour une collecte à domicile.',
            'adresse_collecte.max' => 'L\'adresse de collecte ne peut pas dépasser :max caractères.',
            'message.max' => 'Le message ne peut pas dépasser :max caractères.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }

    /**
     * Validated data, without a collection address when the donation is dropped off on site.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null && ($data['mode_remise'] ?? null) !== 'collecte_a_domicile') {
            $data['adresse_collecte'] = null;
        }

        return $data;
    }
}
