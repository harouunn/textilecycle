<?php

/*
|--------------------------------------------------------------------------
| Module « Dépôt & vêtements »
|--------------------------------------------------------------------------
|
| Routes de ce module uniquement. Ce fichier est chargé par routes/web.php.
|
| Convention :
|   - front office : URL /depot/..., noms de route "depot.*"
|   - back office  : Route::middleware('auth')->prefix('admin/depot')->name('admin.depot.')
|
*/

use App\Http\Controllers\Admin\Depot\CategorieController;
use App\Http\Controllers\Admin\Depot\VetementController;
use App\Http\Controllers\Depot\CatalogueController;
use App\Http\Controllers\Depot\MesDepotsController;
use Illuminate\Support\Facades\Route;

// Front office : catalogue public
Route::prefix('depot')->name('depot.')->group(function () {
    Route::get('catalogue', [CatalogueController::class, 'index'])->name('catalogue.index');
    Route::get('catalogue/{vetement}', [CatalogueController::class, 'show'])->name('catalogue.show');

    // « Déposer un vêtement » (create) et « Mes dépôts » (index, edit, update, destroy)
    Route::middleware('auth')->group(function () {
        Route::resource('mes-depots', MesDepotsController::class)
            ->except('show')
            ->parameters(['mes-depots' => 'vetement']);
    });
});

// Back office
Route::middleware('auth')->prefix('admin/depot')->name('admin.depot.')->group(function () {
    Route::resource('categories', CategorieController::class)
        ->except('show')
        ->parameters(['categories' => 'categorie']);

    Route::resource('vetements', VetementController::class);
});
