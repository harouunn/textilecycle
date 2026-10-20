<?php

namespace App\Http\Requests\Upcycling;

use Illuminate\Foundation\Http\FormRequest;

class AssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $p = $this->route('projet');

        return $this->user() !== null && (! $p || $this->user()->can('assist', $p));
    }

    public function rules(): array
    {
        return ['idee' => [$this->routeIs('upcycling.ia.projet') ? 'required' : 'sometimes', 'string', 'min:10', 'max:2000'],
            'langue' => [$this->routeIs('upcycling.ia.traduction') ? 'required' : 'sometimes', 'in:fr,ar,en']];
    }
}
