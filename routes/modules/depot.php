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
use App\Http\Controllers\Admin\Depot\CycleController;
use App\Http\Controllers\Admin\Depot\ExportController;
use App\Http\Controllers\Admin\Depot\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\Depot\VetementController;
use App\Http\Controllers\Depot\BonReservationController;
use App\Http\Controllers\Depot\CatalogueController;
use App\Http\Controllers\Depot\MesDepotsController;
use App\Http\Controllers\Depot\ReservationController;
use Illuminate\Support\Facades\Route;

// Front office : catalogue public
Route::prefix('depot')->name('depot.')->group(function () {
    Route::get('catalogue', [CatalogueController::class, 'index'])->name('catalogue.index');
    Route::get('catalogue/{vetement}', [CatalogueController::class, 'show'])->name('catalogue.show');

    Route::middleware(['auth', 'verified'])->group(function () {
        // « Déposer un vêtement » (create) et « Mes dépôts » (index, edit, update, destroy)
        Route::post('mes-depots/analyser', [MesDepotsController::class, 'analyser'])->name('mes-depots.analyser');
        Route::resource('mes-depots', MesDepotsController::class)
            ->except('show')
            ->parameters(['mes-depots' => 'vetement']);

        // « Réserver ce vêtement » et « Mes réservations »
        Route::get('catalogue/{vetement}/reserver', [ReservationController::class, 'create'])->name('reservations.create');
        Route::post('catalogue/{vetement}/reserver', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('mes-reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::patch('mes-reservations/{reservation}/annuler', [ReservationController::class, 'annuler'])->name('reservations.annuler');
        Route::get('mes-reservations/{reservation}/bon', BonReservationController::class)->name('reservations.bon');
    });
});

// Back office
Route::middleware('auth')->prefix('admin/depot')->name('admin.depot.')->group(function () {
    Route::resource('categories', CategorieController::class)
        ->except('show')
        ->parameters(['categories' => 'categorie']);

    Route::get('export/vetements', [ExportController::class, 'vetements'])->name('export.vetements');
    Route::get('export/reservations', [ExportController::class, 'reservations'])->name('export.reservations');

    Route::post('vetements/analyser', [VetementController::class, 'analyser'])->name('vetements.analyser');
    Route::patch('vetements/{vetement}/approuver', [VetementController::class, 'approuver'])->name('vetements.approuver');
    Route::patch('vetements/{vetement}/refuser', [VetementController::class, 'refuser'])->name('vetements.refuser');
    Route::resource('vetements', VetementController::class);

    // Cycle du vêtement : il continue sa vie dans les autres gestions
    Route::post('vetements/{vetement}/reparer', [CycleController::class, 'reparer'])->name('vetements.reparer');
    Route::post('vetements/{vetement}/transformer', [CycleController::class, 'transformer'])->name('vetements.transformer');
    Route::post('vetements/{vetement}/donner', [CycleController::class, 'donner'])->name('vetements.donner');

    Route::patch('reservations/{reservation}/confirmer', [AdminReservationController::class, 'confirmer'])->name('reservations.confirmer');
    Route::patch('reservations/{reservation}/retirer', [AdminReservationController::class, 'retirer'])->name('reservations.retirer');
    Route::patch('reservations/{reservation}/annuler', [AdminReservationController::class, 'annuler'])->name('reservations.annuler');
    Route::resource('reservations', AdminReservationController::class)->only(['index', 'show', 'destroy']);
});
