<?php

/*
|--------------------------------------------------------------------------
| Module « Associations & dons »
|--------------------------------------------------------------------------
|
| Routes de ce module uniquement. Ce fichier est chargé par routes/web.php.
|
| Convention :
|   - front office : URL /dons/..., noms de route "dons.*"
|   - back office  : Route::middleware('auth')->prefix('admin/dons')->name('admin.dons.')
|
*/

use App\Http\Controllers\Dons\Admin\AssociationController as AdminAssociationController;
use App\Http\Controllers\Dons\Admin\DonController as AdminDonController;
use App\Http\Controllers\Dons\AssociationController;
use App\Http\Controllers\Dons\DonController;
use Illuminate\Support\Facades\Route;

// Front office
Route::prefix('dons')->name('dons.')->group(function () {
    Route::get('/', [AssociationController::class, 'index'])->name('index');
    Route::get('/associations/{association}', [AssociationController::class, 'show'])->name('associations.show');

    Route::middleware('auth')->group(function () {
        Route::get('/associations/{association}/faire-un-don', [DonController::class, 'create'])->name('create');
        Route::post('/associations/{association}/faire-un-don', [DonController::class, 'store'])->name('store');
        Route::get('/mes-dons', [DonController::class, 'index'])->name('mes-dons');
        Route::delete('/mes-dons/{don}', [DonController::class, 'destroy'])->name('cancel');
    });
});

// Back office
Route::middleware('auth')->prefix('admin/dons')->name('admin.dons.')->group(function () {
    Route::resource('associations', AdminAssociationController::class);
    Route::patch('dons/{don}/statut', [AdminDonController::class, 'updateStatut'])->name('dons.statut');
    Route::resource('dons', AdminDonController::class);
});
