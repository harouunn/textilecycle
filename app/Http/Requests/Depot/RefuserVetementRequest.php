<?php

namespace App\Http\Requests\Depot;

use App\Http\Requests\Depot\Concerns\MessagesEnFrancais;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Refus d'un dépôt par l'admin : le motif est affiché au déposant dans « Mes dépôts ».
 */
class RefuserVetementRequest extends FormRequest
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
            'motif_refus' => ['required', 'string', 'max:500'],
        ];
    }
}
