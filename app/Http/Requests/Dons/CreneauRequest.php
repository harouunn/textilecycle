<?php

namespace App\Http\Requests\Dons;

use App\Models\Association;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Espace association : ouverture d'un créneau de collecte à domicile.
 * Authorization (responsable of the association) is checked by AssociationPolicy::gererCreneaux in the controller.
 */
class CreneauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'association_id' => ['required', Rule::exists('associations', 'id')->withoutTrashed()],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'capacite_max' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'association_id.required' => 'Choisissez l\'association.',
            'association_id.exists' => 'L\'association sélectionnée est invalide.',
            'date.required' => 'La date est obligatoire.',
            'date.date' => 'La date n\'est pas valide.',
            'date.after_or_equal' => 'Le créneau doit être aujourd\'hui ou plus tard.',
            'heure_debut.required' => 'L\'heure de début est obligatoire.',
            'heure_debut.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'heure_fin.required' => 'L\'heure de fin est obligatoire.',
            'heure_fin.date_format' => 'L\'heure de fin doit être au format HH:MM.',
            'heure_fin.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'capacite_max.required' => 'Le nombre de collectes est obligatoire.',
            'capacite_max.integer' => 'Le nombre de collectes doit être un nombre entier.',
            'capacite_max.min' => 'Un créneau accepte au moins :min collecte.',
            'capacite_max.max' => 'Un créneau ne peut pas dépasser :max collectes.',
        ];
    }

    public function association(): Association
    {
        return Association::query()->findOrFail($this->validated('association_id'));
    }
}
