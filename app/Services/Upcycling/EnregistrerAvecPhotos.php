<?php

namespace App\Services\Upcycling;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class EnregistrerAvecPhotos
{
    public function enregistrer(Model $model, array $donnees, Request $request, array $champs, string $dossier): Model
    {
        $nouveaux = [];
        $anciens = [];
        $disque = Storage::disk('public');
        try {
            foreach ($champs as $champ) {
                if ($request->hasFile($champ)) {
                    $chemin = $request->file($champ)->store($dossier, 'public');
                    if (! $chemin) {
                        throw new RuntimeException('Impossible d’enregistrer l’image.');
                    }
                    $nouveaux[] = $chemin;
                    if ($model->{$champ}) {
                        $anciens[] = $model->{$champ};
                    }
                    $donnees[$champ] = $chemin;
                }
            }
            $model->getConnection()->transaction(function () use ($model, $donnees) {
                $model->fill($donnees);
                if (! $model->save()) {
                    throw new RuntimeException('Impossible d’enregistrer les données.');
                }
            });
        } catch (Throwable $exception) {
            $disque->delete($nouveaux);
            throw $exception;
        }
        // Attend aussi le commit d’une éventuelle transaction englobante.
        if ($anciens) {
            $model->getConnection()->afterCommit(fn () => $disque->delete($anciens));
        }

        return $model;
    }
}
