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
use App\Http\Controllers\Upcycling\EtapeProjetController;
use App\Http\Controllers\Upcycling\GalerieController;
use App\Http\Controllers\Upcycling\MesProjetsController;
use Illuminate\Support\Facades\Route;

// Front office
Route::prefix('upcycling')->name('upcycling.')->group(function () {
    Route::get('/', [GalerieController::class, 'index'])->name('index');

    Route::middleware('auth')->group(function () {
        Route::get('/mes-projets', [MesProjetsController::class, 'index'])->name('mes-projets');
        Route::resource('projets', MesProjetsController::class)->except(['index', 'show']);
        Route::resource('projets.etapes', EtapeProjetController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    });

    // Déclarée après "projets/create" pour ne pas l'intercepter
    Route::get('/projets/{projet}', [GalerieController::class, 'show'])->name('projets.show');
});

// Back office
Route::middleware('auth')->prefix('admin/upcycling')->name('admin.upcycling.')->group(function () {
    Route::resource('projets', AdminProjetUpcyclingController::class);
    Route::resource('projets.etapes', AdminEtapeProjetController::class)->only(['store', 'edit', 'update', 'destroy']);
});
