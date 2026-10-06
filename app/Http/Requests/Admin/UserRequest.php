<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Back office: create / update a user account.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->route('user') === null;

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins :min caractères.',
            'name.max' => 'Le nom ne peut pas dépasser :max caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.lowercase' => 'L\'adresse e-mail doit être en minuscules.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.max' => 'L\'adresse e-mail ne peut pas dépasser :max caractères.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ];
    }

    /**
     * Validated data, without the password when it was left empty on update.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null && blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
