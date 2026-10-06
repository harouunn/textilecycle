<?php

namespace App\Http\Controllers\Depot\Concerns;

use App\Models\Vetement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        // Photo enregistrée par « Analyser la photo » (création uniquement)
        $photoAnalysee = $vetement === null ? $request->session()->pull(Vetement::SESSION_PHOTO_ANALYSEE) : null;

        if ($request->hasFile('photo')) {
            $vetement?->deletePhoto();
            if ($photoAnalysee) {
                Storage::disk('public')->delete($photoAnalysee);
            }
            $data['photo'] = $request->file('photo')->store(Vetement::PHOTO_DIRECTORY, 'public');
        } elseif ($photoAnalysee) {
            $data['photo'] = $photoAnalysee;
        }

        return $data;
    }
}
