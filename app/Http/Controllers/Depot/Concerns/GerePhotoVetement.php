<?php

namespace App\Http\Controllers\Depot\Concerns;

use App\Models\Vetement;
use Illuminate\Http\Request;

/**
 * Enregistre la photo envoyée dans storage/app/public/vetements
 * et remplace l'ancienne photo lors d'une modification.
 */
trait GerePhotoVetement
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function avecPhoto(Request $request, array $data, ?Vetement $vetement = null): array
    {
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $vetement?->deletePhoto();
            $data['photo'] = $request->file('photo')->store(Vetement::PHOTO_DIRECTORY, 'public');
        }

        return $data;
    }
}
