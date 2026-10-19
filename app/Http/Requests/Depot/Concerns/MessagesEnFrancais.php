<?php

namespace App\Http\Requests\Depot\Concerns;

use Illuminate\Validation\Rules\Enum;

/**
 * Messages de validation en français pour le module « Dépôt & vêtements ».
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
            'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
            // Rule::enum() est un objet règle : son message se personnalise par le nom de sa classe.
            Enum::class => 'La valeur sélectionnée pour :attribute est invalide.',
            'unique' => 'Ce :attribute est déjà utilisé.',
            'date' => 'Le champ :attribute doit être une date valide.',
            'boolean' => 'Le champ :attribute doit être oui ou non.',
            'min' => ['numeric' => 'Le champ :attribute doit être supérieur ou égal à :min.'],
            'max' => [
                'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
                'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
                'file' => 'Le fichier :attribute ne doit pas dépasser :max Ko.',
            ],
            'couleur.regex' => 'La couleur doit être au format #RRGGBB (ex. #3d7a4f).',
            'date_retrait.after' => 'Le retrait est possible à partir de demain.',
            'date_retrait.before_or_equal' => 'Le retrait doit avoir lieu dans les 14 prochains jours.',
            'telephone.regex' => 'Le numéro de téléphone n\'est pas valide (ex. 22 123 456).',
            'nom.unique' => 'Une catégorie porte déjà ce nom.',
            'date_depot.before_or_equal' => 'La date de dépôt ne peut pas être dans le futur.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.uploaded' => 'La photo n\'a pas pu être envoyée (2 Mo maximum).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom',
            'description' => 'description',
            'icone' => 'icône',
            'categorie_id' => 'catégorie',
            'user_id' => 'déposant',
            'titre' => 'titre',
            'taille' => 'taille',
            'genre' => 'genre',
            'matiere' => 'matière',
            'etat' => 'état',
            'photo' => 'photo',
            'statut' => 'statut',
            'date_depot' => 'date de dépôt',
            'motif_refus' => 'motif du refus',
            'couleur' => 'couleur',
            'ordre' => 'ordre d\'affichage',
            'active' => 'catégorie active',
            'date_retrait' => 'date de retrait',
            'creneau' => 'créneau',
            'telephone' => 'téléphone',
            'message' => 'message',
            'motif_annulation' => 'motif de l\'annulation',
        ];
    }
}
