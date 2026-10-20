<?php

/*
|--------------------------------------------------------------------------
| Module « Upcycling »
|--------------------------------------------------------------------------
|
| Routes de ce module uniquement. Ce fichier est chargé par routes/web.php.
|
| Convention :
|   - front office : URL /upcycling/..., noms de route "upcycling.*"
|   - back office  : Route::middleware('auth')->prefix('admin/upcycling')->name('admin.upcycling.')
|
*/

use App\Http\Controllers\Admin\Upcycling\EtapeProjetController as AdminEtapeProjetController;
use App\Http\Controllers\Admin\Upcycling\ProjetUpcyclingController as AdminProjetUpcyclingController;
use App\Http\Controllers\Upcycling\AssistantController;
use App\Http\Controllers\Upcycling\EtapeProjetController;
use App\Http\Controllers\Upcycling\FavoriController;
use App\Http\Controllers\Upcycling\GalerieController;
use App\Http\Controllers\Upcycling\MesProjetsController;
use App\Http\Controllers\Upcycling\ModerationController;
use Illuminate\Support\Facades\Route;

// Front office
Route::prefix('upcycling')->name('upcycling.')->group(function () {
    Route::get('/', [GalerieController::class, 'index'])->name('index');

    Route::middleware('auth')->group(function () {
        Route::get('mes-favoris', [FavoriController::class, 'index'])->name('mes-favoris');
        Route::post('projets/{projet}/favori', [FavoriController::class, 'store'])->name('favoris.store');
        Route::delete('projets/{projet}/favori', [FavoriController::class, 'destroy'])->name('favoris.destroy');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('ia/projet', [AssistantController::class, 'projet'])->middleware('throttle:10,1')->name('ia.projet');
        Route::post('projets/{projet}/ia/etapes', [AssistantController::class, 'etapes'])->middleware('throttle:10,1')->name('ia.etapes');
        Route::post('projets/{projet}/etapes/accepter', [AssistantController::class, 'enregistrerEtapes'])->name('ia.etapes.enregistrer');
        Route::post('projets/{projet}/ia/traduction', [AssistantController::class, 'traduction'])->middleware('throttle:10,1')->name('ia.traduction');
        Route::put('projets/{projet}/traductions/{langue}', [AssistantController::class, 'confirmerTraduction'])->whereIn('langue', ['fr', 'ar', 'en'])->name('ia.traduction.confirmer');
        Route::post('projets/{projet}/soumettre', [ModerationController::class, 'soumettre'])->name('projets.soumettre');
        Route::get('/mes-projets', [MesProjetsController::class, 'index'])->name('mes-projets');
        Route::resource('projets', MesProjetsController::class)->except(['index', 'show']);
        Route::resource('projets.etapes', EtapeProjetController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    });

    // Déclarée après "projets/create" pour ne pas l'intercepter
    Route::get('/projets/{projet}', [GalerieController::class, 'show'])->name('projets.show');
});

// Back office
Route::middleware('auth')->prefix('admin/upcycling')->name('admin.upcycling.')->group(function () {
    Route::post('projets/{projet}/publier', [ModerationController::class, 'publier'])->middleware('can:moderate,projet')->name('projets.publier');
    Route::post('projets/{projet}/rejeter', [ModerationController::class, 'rejeter'])->middleware('can:moderate,projet')->name('projets.rejeter');
    Route::resource('projets', AdminProjetUpcyclingController::class);
    Route::resource('projets.etapes', AdminEtapeProjetController::class)->only(['store', 'edit', 'update', 'destroy']);
});
