<?php

namespace App\Http\Requests\Upcycling;

use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use Illuminate\Validation\Rule;

class EtapeProjetRequest extends UpcyclingRequest
{
    protected $errorBag = 'etape';

    public function authorize(): bool
    {
        $projet = $this->route('projet');
        abort_unless($projet instanceof ProjetUpcycling && $projet->exists, 404);
        if ($this->routeIs('upcycling.*') && ! ($this->user()?->can('update', $projet) ?? false)) {
            return false;
        }
        $etape = $this->route('etape');
        if ($etape instanceof EtapeProjet) {
            abort_unless($etape->projet_upcycling_id === $projet->id, 404);
        }

        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        // L’URL fait autorité, jamais un identifiant fourni dans le formulaire.
        $this->merge(['projet_upcycling_id' => $this->route('projet')?->id]);
    }

    public function rules(): array
    {
        $projet = $this->route('projet');
        $etape = $this->route('etape');

        return [
            'projet_upcycling_id' => ['required', 'integer', 'exists:projets_upcycling,id'],
            'numero' => ['bail', 'required', 'integer', 'min:1', 'max:100',
                Rule::unique('etapes_projet', 'numero')->where('projet_upcycling_id', $projet->id)->ignore($etape?->id)],
            'titre' => ['bail', 'required', 'string', 'min:3', 'max:150'],
            'contenu' => ['bail', 'required', 'string', 'min:10', 'max:5000'],
            'photo' => $this->reglesPhoto($etape, 'photo'),
        ];
    }

    public function messages(): array
    {
        return $this->messagesPhoto() + [
            'projet_upcycling_id.required' => 'Le projet associé est obligatoire.',
            'projet_upcycling_id.exists' => 'Le projet associé n’existe pas.',
            'projet_upcycling_id.integer' => 'Le projet associé est invalide.',
            'numero.required' => 'Le numéro de l’étape est obligatoire.',
            'titre.required' => 'Le titre est obligatoire.',
            'contenu.required' => 'Les consignes sont obligatoires.',
            'photo.required' => 'La photo de l’étape est obligatoire.',
            'string' => 'Le champ :attribute doit être un texte.',
            'numero.integer' => 'Le numéro doit être un nombre entier supérieur ou égal à 1.',
            'numero.min' => 'Le numéro doit être supérieur ou égal à :min.',
            'numero.max' => 'Le numéro ne peut pas dépasser :max.',
            'numero.unique' => 'Ce numéro est déjà utilisé pour une étape de ce projet.',
            'titre.min' => 'Le titre doit contenir au moins :min caractères.',
            'titre.max' => 'Le titre ne peut pas dépasser :max caractères.',
            'contenu.min' => 'Les consignes doivent contenir au moins :min caractères.',
            'contenu.max' => 'Les consignes ne peuvent pas dépasser :max caractères.',
            'photo.max' => 'L’image ne doit pas dépasser 2 Mo.',
        ];
    }

    public function attributes(): array
    {
        return ['titre' => 'titre', 'contenu' => 'consignes'];
    }

    public function donnees(?EtapeProjet $etape = null): array
    {
        return $this->safe()->except(['photo', 'projet_upcycling_id']);
    }
}
