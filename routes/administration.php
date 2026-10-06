<?php

/*
|--------------------------------------------------------------------------
| Administration (commun à tous les modules)
|--------------------------------------------------------------------------
|
| Gestion des utilisateurs et statistiques du back office.
| Ce fichier est chargé par routes/web.php.
|
*/

use App\Http\Controllers\Admin\StatistiqueController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::get('statistiques', [StatistiqueController::class, 'index'])->name('statistiques');
});
