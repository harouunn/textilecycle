<?php

namespace App\Http\Requests\Depot;

use App\Http\Requests\Depot\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Annulation d'une réservation : le motif est obligatoire quand c'est l'admin qui annule.
 */
class AnnulerReservationRequest extends FormRequest
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
        return [
            'motif_annulation' => [$this->routeIs('admin.*') ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }
}
