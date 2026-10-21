<?php

namespace App\Http\Requests\Dons;

use App\Enums\Dons\ModeRemise;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\CreneauCollecte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Front office: a logged-in user proposes a donation to an association.
 * For a home collection the donor picks one of the association's available slots; its date becomes the date_remise.
 * The slot is checked again under lock by DonWorkflowService::proposer (overbooking).
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
        $association = $this->route('association');
        $associationId = $association instanceof Association ? $association->id : 0;
        $collecte = ModeRemise::CollecteADomicile->value;

        $rules = $this->donRules();
        $rules['date_remise'] = ["exclude_if:mode_remise,{$collecte}", ...$rules['date_remise']];

        return [
            ...$rules,
            'campagne_id' => ['nullable', 'integer', Rule::in(
                CampagneCollecte::query()->enCours()->where('association_id', $associationId)->pluck('id')->all()
            )],
            'creneau_id' => ["exclude_unless:mode_remise,{$collecte}", 'required', 'integer', Rule::in(
                CreneauCollecte::query()->disponibles()->where('association_id', $associationId)->pluck('id')->all()
            )],
            // Jeton de l'analyse photo (IA) : le résultat lui-même est relu côté serveur (AnalysePhotoDon).
            'ia_jeton' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->donMessages(),
            'campagne_id.integer' => 'Cette campagne n\'est pas ouverte pour cette association.',
            'campagne_id.in' => 'Cette campagne n\'est pas ouverte pour cette association.',
            'creneau_id.required' => 'Choisissez un créneau de collecte.',
            'creneau_id.integer' => 'Ce créneau n\'est plus disponible. Choisissez-en un autre.',
            'creneau_id.in' => 'Ce créneau n\'est plus disponible. Choisissez-en un autre.',
            'ia_jeton.uuid' => 'L\'analyse de la photo est invalide : choisissez à nouveau la photo.',
        ];
    }
}
