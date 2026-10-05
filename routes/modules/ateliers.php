<?php

/*
|--------------------------------------------------------------------------
| Module « Ateliers & réparations »
|--------------------------------------------------------------------------
|
| Routes de ce module uniquement. Ce fichier est chargé par routes/web.php.
|
| Convention :
|   - front office : URL /ateliers/..., noms de route "ateliers.*"
|   - back office  : Route::middleware('auth')->prefix('admin/ateliers')->name('admin.ateliers.')
|
*/

use App\Http\Controllers\Admin\Ateliers\AtelierController as AdminAtelierController;
use App\Http\Controllers\Admin\Ateliers\DemandeReparationController as AdminDemandeReparationController;
use App\Http\Controllers\Ateliers\AtelierController;
use App\Http\Controllers\Ateliers\DemandeReparationController;
use Illuminate\Support\Facades\Route;

// Front office
Route::prefix('ateliers')->name('ateliers.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('mes-demandes', [DemandeReparationController::class, 'index'])->name('demandes.index');
        Route::delete('mes-demandes/{demande}', [DemandeReparationController::class, 'destroy'])->whereNumber('demande')->name('demandes.destroy');
        Route::get('{atelier}/demander', [DemandeReparationController::class, 'create'])->whereNumber('atelier')->name('demandes.create');
        Route::post('{atelier}/demander', [DemandeReparationController::class, 'store'])->whereNumber('atelier')->name('demandes.store');
    });

    Route::get('/', [AtelierController::class, 'index'])->name('index');
    Route::get('{atelier}', [AtelierController::class, 'show'])->whereNumber('atelier')->name('show');
});

// Back office
Route::middleware('auth')->prefix('admin/ateliers')->name('admin.ateliers.')->group(function () {
    Route::patch('demandes/{demande}/traiter', [AdminDemandeReparationController::class, 'traiter'])->name('demandes.traiter');
    Route::resource('demandes', AdminDemandeReparationController::class)
        ->parameters(['demandes' => 'demande']);
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('ateliers', AdminAtelierController::class)
        ->where(['atelier' => '[0-9]+']);
});
