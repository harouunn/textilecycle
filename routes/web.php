<?php

use App\Http\Controllers\AtelierController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Front office
Route::view('/', 'front.home')->name('home');

// Back office
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    
    // Ateliers CRUD
    Route::resource('ateliers', AtelierController::class);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/administration.php';

// Modules (one file per module, edited only by its team)
require __DIR__.'/modules/depot.php';
require __DIR__.'/modules/ateliers.php';
require __DIR__.'/modules/upcycling.php';
require __DIR__.'/modules/dons.php';
