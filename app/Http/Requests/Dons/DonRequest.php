<?php

namespace App\Http\Requests\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Don;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Back office: create / update a donation.
 * The statut is chosen only at creation; afterwards it changes through the workflow (UpdateDonStatutRequest).
 * Authorization is checked by DonPolicy in the controller.
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
        $editing = $don instanceof Don;
        $checkDate = ! $editing || $this->input('date_remise') !== $don->date_remise?->format('Y-m-d');

        return [
            'association_id' => ['required', Rule::exists('associations', 'id')->withoutTrashed()],
            'user_id' => ['required', 'exists:users,id'],
            ...$this->donRules($checkDate),
            ...($editing ? [] : ['statut' => ['required', Rule::enum(DonStatut::class)]]),
        ];
    }
}
