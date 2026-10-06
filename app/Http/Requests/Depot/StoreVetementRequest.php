<?php

namespace App\Http\Requests\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\StatutVetement;
use App\Enums\Depot\Taille;
use App\Http\Requests\Depot\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Utilisée par le back office (admin.depot.*) et par le formulaire « Déposer un vêtement ».
 * Seul le back office peut choisir le déposant et le statut.
 */
class StoreVetementRequest extends FormRequest
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
            'categorie_id' => ['required', 'integer', 'exists:categories,id'],
            'titre' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'taille' => ['required', Rule::enum(Taille::class)],
            'genre' => ['required', Rule::enum(Genre::class)],
            'matiere' => ['required', 'string', 'max:100'],
            'etat' => ['required', Rule::enum(Etat::class)],
            'photo' => ['nullable', 'image', 'mimes:jpg,png,webp', 'max:2048'],
            'date_depot' => ['required', 'date', 'before_or_equal:today'],
        ];

        if ($this->isBackOffice()) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
            $rules['statut'] = ['required', Rule::enum(StatutVetement::class)];
        }

        return $rules;
    }

    protected function isBackOffice(): bool
    {
        return $this->routeIs('admin.*');
    }
}
