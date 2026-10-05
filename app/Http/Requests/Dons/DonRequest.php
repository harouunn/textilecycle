<?php

namespace App\Http\Requests\Dons;

use App\Models\Don;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Back office: create / update a donation.
 */
class DonRequest extends FormRequest
{
    use ValidatesDon;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // On update, an unchanged (possibly past) date stays valid.
        $don = $this->route('don');
        $checkDate = ! $don instanceof Don || $this->input('date_remise') !== $don->date_remise?->format('Y-m-d');

        return [
            'association_id' => ['required', 'exists:associations,id'],
            'user_id' => ['required', 'exists:users,id'],
            ...$this->donRules($checkDate),
            'statut' => ['required', Rule::in(array_keys(Don::STATUTS))],
        ];
    }
}
