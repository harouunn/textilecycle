<?php

namespace App\Http\Requests\Upcycling;

use App\Enums\Upcycling\Difficulte;
use App\Enums\Upcycling\StatutProjet;
use App\Models\ProjetUpcycling;
use Illuminate\Validation\Rule;

class ProjetUpcyclingRequest extends UpcyclingRequest
{
    protected $errorBag = 'projet';

    public function authorize(): bool
    {
        $projet = $this->route('projet');
        if ($projet && $this->routeIs('upcycling.*')) {
            return $this->user()?->can('update', $projet) ?? false;
        }

        return $this->user() !== null;
    }

    public function rules(): array
    {
        $projet = $this->route('projet');

        return [
            'titre' => ['bail', 'required', 'string', 'min:5', 'max:150'],
            'description' => ['bail', 'required', 'string', 'min:20', 'max:5000'],
            'vetement_origine' => ['bail', 'required', 'string', 'min:3', 'max:255'],
            'resultat' => ['bail', 'required', 'string', 'min:3', 'max:255'],
            'difficulte' => ['bail', 'required', Rule::enum(Difficulte::class)],
            'duree_minutes' => ['bail', 'required', 'integer', 'min:5', 'max:1440'],
            'materiel_necessaire' => ['bail', 'required', 'string', 'min:3', 'max:2000'],
            'photo_avant' => $this->reglesPhoto($projet, 'photo_avant'),
            'photo_apres' => $this->reglesPhoto($projet, 'photo_apres'),
            'statut' => ['bail', Rule::requiredIf($this->routeIs('admin.*')), 'nullable', Rule::enum(StatutProjet::class)],
        ];
    }

    public function messages(): array
    {
        return $this->messagesPhoto() + [
            'titre.required' => 'Le titre est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'vetement_origine.required' => 'Le vêtement d’origine est obligatoire.',
            'resultat.required' => 'Le résultat attendu est obligatoire.',
            'difficulte.required' => 'La difficulté est obligatoire.',
            'duree_minutes.required' => 'La durée est obligatoire.',
            'materiel_necessaire.required' => 'Indiquez au moins un élément de matériel non vide.',
            'statut.required' => 'Le statut est obligatoire.',
            'photo_avant.required' => 'La photo avant est obligatoire.',
            'photo_apres.required' => 'La photo après est obligatoire.',
            'string' => 'Le champ :attribute doit être un texte.',
            'titre.min' => 'Le titre doit contenir au moins :min caractères.',
            'titre.max' => 'Le titre ne peut pas dépasser :max caractères.',
            'description.min' => 'La description doit contenir au moins :min caractères.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
            'vetement_origine.min' => 'Le vêtement d’origine doit contenir au moins :min caractères.',
            'vetement_origine.max' => 'Le vêtement d’origine ne peut pas dépasser :max caractères.',
            'resultat.min' => 'Le résultat attendu doit contenir au moins :min caractères.',
            'resultat.max' => 'Le résultat attendu ne peut pas dépasser :max caractères.',
            'difficulte.enum' => 'La difficulté doit être facile, moyen ou difficile.',
            'duree_minutes.integer' => 'La durée doit être un nombre entier supérieur à zéro.',
            'duree_minutes.min' => 'La durée doit être d’au moins :min minutes.',
            'duree_minutes.max' => 'La durée ne peut pas dépasser :max minutes (24 heures).',
            'materiel_necessaire.min' => 'Le matériel nécessaire doit contenir au moins :min caractères.',
            'materiel_necessaire.max' => 'Le matériel nécessaire ne peut pas dépasser :max caractères.',
            'photo_avant.max' => 'L’image ne doit pas dépasser 2 Mo.',
            'photo_apres.max' => 'L’image ne doit pas dépasser 2 Mo.',
            'statut.enum' => 'Le statut doit être brouillon ou publié.',
        ];
    }

    public function attributes(): array
    {
        return ['titre' => 'titre', 'description' => 'description', 'vetement_origine' => 'vêtement d’origine',
            'resultat' => 'résultat attendu', 'materiel_necessaire' => 'matériel nécessaire'];
    }

    public function donnees(?ProjetUpcycling $projet = null): array
    {
        // Les fichiers sont gérés avec l’enregistrement, après validation complète.
        $except = ['photo_avant', 'photo_apres'];
        if ($this->routeIs('upcycling.*')) {
            $except[] = 'statut';
        }

        return $this->safe()->except($except);
    }
}
