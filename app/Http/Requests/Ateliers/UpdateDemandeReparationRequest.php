<?php

namespace App\Http\Requests\Ateliers;

use App\Models\DemandeReparation;

class UpdateDemandeReparationRequest extends StoreDemandeReparationRequest
{
    protected function photoDejaEnregistree(): bool
    {
        $demande = $this->route('demande');

        return $demande instanceof DemandeReparation && (bool) $demande->photo;
    }
}
