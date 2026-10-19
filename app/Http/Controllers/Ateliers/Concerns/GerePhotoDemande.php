<?php

namespace App\Http\Controllers\Ateliers\Concerns;

use App\Models\DemandeReparation;
use Illuminate\Http\Request;

/**
 * Enregistre la photo envoyée dans storage/app/public/reparations
 * et remplace l'ancienne photo lors d'une modification.
 */
trait GerePhotoDemande
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function avecPhoto(Request $request, array $data, ?DemandeReparation $demande = null): array
    {
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $demande?->deletePhoto();
            $data['photo'] = $request->file('photo')->store(DemandeReparation::PHOTO_DIRECTORY, 'public');
        }

        return $data;
    }
}
