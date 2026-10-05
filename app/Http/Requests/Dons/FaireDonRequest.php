<?php

namespace App\Http\Requests\Dons;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Front office: a logged-in user proposes a donation to an association.
 */
class FaireDonRequest extends FormRequest
{
    use ValidatesDon;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return $this->donRules();
    }
}
