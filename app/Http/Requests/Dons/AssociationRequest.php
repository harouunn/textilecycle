<?php

namespace App\Http\Requests\Dons;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Back office: create / update an association.
 */
class AssociationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
            'capacite_mensuelle_kg' => $this->filled('capacite_mensuelle_kg') ? $this->input('capacite_mensuelle_kg') : 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:3', 'max:255', Rule::unique('associations', 'nom')->ignore($this->route('association'))],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'telephone' => ['required', 'string', 'regex:/^\+?[0-9 .\-()]{8,20}$/'],
            'email' => ['required', 'email', 'max:255'],
            // http(s) seulement : le lien est affiché tel quel (href) sur le site.
            'site_web' => ['nullable', 'url:http,https', 'max:255'],
            // Liste fermée de formats raster (pas de SVG, qui peut contenir du script). Stocké sur le disque « public »
            // (storage/app/public, servi par php artisan storage:link) sous un nom aléatoire.
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'besoins' => ['required', 'string', 'min:5', 'max:2000'],
            'capacite_mensuelle_kg' => ['required', 'integer', 'min:0', 'max:1000000'],
            'active' => ['boolean'],
            'responsable_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de l\'association est obligatoire.',
            'nom.min' => 'Le nom doit contenir au moins :min caractères.',
            'nom.max' => 'Le nom ne peut pas dépasser :max caractères.',
            'nom.unique' => 'Une association porte déjà ce nom.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins :min caractères.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'adresse.max' => 'L\'adresse ne peut pas dépasser :max caractères.',
            'ville.required' => 'La ville est obligatoire.',
            'ville.max' => 'La ville ne peut pas dépasser :max caractères.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro de téléphone n\'est pas valide (8 à 20 chiffres).',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.max' => 'L\'adresse e-mail ne peut pas dépasser :max caractères.',
            'site_web.url' => 'Le site web doit être une URL valide (ex. https://exemple.org).',
            'site_web.max' => 'Le site web ne peut pas dépasser :max caractères.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Le logo doit être au format JPG, PNG ou WebP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
            'logo.uploaded' => 'Le téléversement du logo a échoué (2 Mo maximum).',
            'besoins.required' => 'Veuillez préciser les besoins de l\'association.',
            'besoins.min' => 'Les besoins doivent contenir au moins :min caractères.',
            'besoins.max' => 'Les besoins ne peuvent pas dépasser :max caractères.',
            'latitude.required_with' => 'Renseignez la latitude et la longitude ensemble.',
            'latitude.numeric' => 'La latitude doit être un nombre (ex. 36.8065).',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.required_with' => 'Renseignez la latitude et la longitude ensemble.',
            'longitude.numeric' => 'La longitude doit être un nombre (ex. 10.1815).',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'capacite_mensuelle_kg.integer' => 'La capacité mensuelle doit être un nombre entier de kilos.',
            'capacite_mensuelle_kg.min' => 'La capacité mensuelle ne peut pas être négative.',
            'capacite_mensuelle_kg.max' => 'La capacité mensuelle ne peut pas dépasser :max kg.',
            'active.boolean' => 'La valeur du champ « active » est invalide.',
            'responsable_id.exists' => 'Le responsable sélectionné est invalide.',
        ];
    }
}
