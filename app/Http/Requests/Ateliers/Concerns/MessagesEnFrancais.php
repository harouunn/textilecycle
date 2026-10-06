<?php

namespace App\Http\Requests\Ateliers\Concerns;

use Illuminate\Validation\Rules\Enum;

/**
 * Messages de validation en français pour le module « Ateliers & réparations ».
 */
trait MessagesEnFrancais
{
    /**
     * @return array<string, string|array<string, string>>
     */
    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être une chaîne de caractères.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
            // Rule::enum() est un objet règle : son message se personnalise par le nom de sa classe.
            Enum::class => 'La valeur sélectionnée pour :attribute est invalide.',
            'date' => 'Le champ :attribute doit être une date valide.',
            'min' => ['numeric' => 'Le champ :attribute doit être supérieur ou égal à :min.'],
            'max' => [
                'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
                'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
                'file' => 'Le fichier :attribute ne doit pas dépasser :max Ko.',
            ],
            'description.required_without' => 'Décrivez le problème ou ajoutez une photo : le diagnostic a besoin de l\'un des deux.',
            'photo.required_without' => 'Ajoutez une photo ou décrivez le problème : le diagnostic a besoin de l\'un des deux.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.uploaded' => 'La photo n\'a pas pu être envoyée (2 Mo maximum).',
            'reponse_atelier.required_if' => 'Expliquez au client pourquoi la demande est refusée.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'atelier_id' => 'atelier',
            'user_id' => 'client',
            'titre' => 'titre',
            'type_vetement' => 'type de vêtement',
            'description' => 'description',
            'photo' => 'photo',
            'statut' => 'statut',
            'cout_final' => 'coût final',
            'date_prevue' => 'date prévue',
            'reponse_atelier' => 'réponse de l\'atelier',
        ];
    }
}
