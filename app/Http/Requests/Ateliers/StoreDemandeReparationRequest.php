<?php

namespace App\Http\Requests\Ateliers;

use App\Enums\Ateliers\TypeVetement;
use App\Http\Requests\Ateliers\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Utilisée par le back office (admin.ateliers.demandes.*) et par le formulaire « Demander une réparation ».
 * Le diagnostic a besoin d'une description ou d'une photo (au moins l'une des deux).
 * Seul le back office choisit l'atelier et le client ; en front, l'atelier vient de l'URL.
 */
class StoreDemandeReparationRequest extends FormRequest
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
        $rules = [
            'titre' => ['required', 'string', 'max:150'],
            'type_vetement' => ['required', Rule::enum(TypeVetement::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,png,webp', 'max:2048'],
        ];

        if (! $this->photoDejaEnregistree()) {
            $rules['description'][] = 'required_without:photo';
            $rules['photo'][] = 'required_without:description';
        }

        if ($this->isBackOffice()) {
            $rules['atelier_id'] = ['required', 'integer', 'exists:ateliers,id'];
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    protected function isBackOffice(): bool
    {
        return $this->routeIs('admin.*');
    }

    /**
     * En modification, une photo déjà enregistrée suffit au diagnostic.
     */
    protected function photoDejaEnregistree(): bool
    {
        return false;
    }
}
