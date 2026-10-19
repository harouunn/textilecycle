<?php

namespace App\Http\Requests\Depot;

use App\Enums\Depot\Creneau;
use App\Http\Requests\Depot\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaire « Réserver ce vêtement » : retrait entre demain et dans 14 jours.
 */
class StoreReservationRequest extends FormRequest
{
    use MessagesEnFrancais;

    public const DELAI_MAX_JOURS = 14;

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
            'date_retrait' => ['required', 'date', 'after:today', 'before_or_equal:'.today()->addDays(self::DELAI_MAX_JOURS)->toDateString()],
            'creneau' => ['required', Rule::enum(Creneau::class)],
            'telephone' => ['required', 'string', 'regex:/^\+?[0-9 .-]{8,20}$/'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
