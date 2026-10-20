<?php

namespace App\Http\Requests\Upcycling;

use App\Rules\UpcyclingImage;
use Illuminate\Validation\Rule;

class EtapesAssistantRequest extends UpcyclingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assist', $this->route('projet')) ?? false;
    }

    public function rules(): array
    {
        return ['etapes' => 'required|array|min:1|max:12',
            'etapes.*' => 'array:numero,titre,contenu,outils_necessaires,duree_minutes,conseils_securite,photo',
            'etapes.*.numero' => ['required', 'integer', 'min:1', 'max:100', 'distinct', Rule::unique('etapes_projet', 'numero')->where('projet_upcycling_id', $this->route('projet')->id)],
            'etapes.*.titre' => 'required|string|min:3|max:150', 'etapes.*.contenu' => 'required|string|min:10|max:5000',
            'etapes.*.outils_necessaires' => 'required|string|max:2000', 'etapes.*.duree_minutes' => 'required|integer|min:1|max:1440',
            'etapes.*.conseils_securite' => 'required|string|max:2000',
            'etapes.*.photo' => ['bail', 'required', 'file', new UpcyclingImage, 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048']];
    }
}
