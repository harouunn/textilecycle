<?php

namespace App\Http\Controllers\Depot\Concerns;

use App\Http\Requests\Depot\AnalyserVetementRequest;
use App\Models\Categorie;
use App\Models\Vetement;
use App\Services\Depot\ClassificateurVetement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Bouton « Analyser la photo » : la photo est enregistrée, classée, puis le formulaire de création
 * est réaffiché avec les champs vides pré-remplis. La photo analysée est gardée en session :
 * elle sera utilisée à l'enregistrement si le déposant n'en choisit pas une autre (voir GerePhotoVetement).
 */
trait AnalysePhotoVetement
{
    protected function analyserPhoto(AnalyserVetementRequest $request, string $routeFormulaire): RedirectResponse
    {
        if ($ancienne = $request->session()->pull(Vetement::SESSION_PHOTO_ANALYSEE)) {
            Storage::disk('public')->delete($ancienne);
        }

        $chemin = $request->file('photo')->store(Vetement::PHOTO_DIRECTORY, 'public');
        $request->session()->put(Vetement::SESSION_PHOTO_ANALYSEE, $chemin);

        $classement = app(ClassificateurVetement::class)->classer(
            Storage::disk('public')->path($chemin),
            $request->validated('titre'),
            Categorie::query()->orderBy('nom')->get(),
        );

        $suggestions = array_filter([
            'categorie_id' => $classement['categorie_id'],
            'matiere' => $classement['matiere'],
            'etat' => $classement['etat']->value,
            'genre' => $classement['genre']->value,
            'taille' => $classement['taille']?->value,
            'description' => $classement['description'],
        ], fn ($valeur) => $valeur !== null);

        // Les champs déjà remplis par le déposant sont conservés.
        $saisie = array_filter($request->except(['photo', '_token', '_method']), fn ($valeur) => filled($valeur));

        return redirect()
            ->route($routeFormulaire)
            ->withInput([...$suggestions, ...$saisie])
            ->with('analyse', $classement['indices']);
    }
}
