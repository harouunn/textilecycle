<?php

namespace App\Http\Requests\Dons;

use App\Enums\Dons\StatutCampagne;
use App\Enums\Dons\TypeArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Back office : création / modification d'une campagne de collecte.
 * Authorization is checked by CampagneCollectePolicy in the controller.
 */
class CampagneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'association_id' => ['required', Rule::exists('associations', 'id')->withoutTrashed()],
            'titre' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'type_articles_cibles' => ['nullable', 'array'],
            'type_articles_cibles.*' => ['distinct', Rule::enum(TypeArticle::class)],
            'objectif_kg' => ['required', 'integer', 'min:10', 'max:1000000'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'statut' => ['required', Rule::enum(StatutCampagne::class)],
            // Formats raster seulement (pas de SVG), stocké sur le disque « public » sous un nom aléatoire.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'association_id.required' => 'Veuillez choisir une association.',
            'association_id.exists' => 'L\'association sélectionnée est invalide.',
            'titre.required' => 'Le titre est obligatoire.',
            'titre.min' => 'Le titre doit contenir au moins :min caractères.',
            'titre.max' => 'Le titre ne peut pas dépasser :max caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins :min caractères.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
            'type_articles_cibles.array' => 'Les types d\'articles ciblés sont invalides.',
            'type_articles_cibles.*.enum' => 'Un des types d\'articles ciblés est invalide.',
            'type_articles_cibles.*.distinct' => 'Un type d\'article est sélectionné deux fois.',
            'objectif_kg.required' => 'L\'objectif est obligatoire.',
            'objectif_kg.integer' => 'L\'objectif doit être un nombre entier de kilos.',
            'objectif_kg.min' => 'L\'objectif doit être d\'au moins :min kg.',
            'objectif_kg.max' => 'L\'objectif ne peut pas dépasser :max kg.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début n\'est pas une date valide.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.date' => 'La date de fin n\'est pas une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.enum' => 'Le statut sélectionné est invalide.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format JPG, PNG ou WebP.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'image.uploaded' => 'Le téléversement de l\'image a échoué (2 Mo maximum).',
        ];
    }
}
