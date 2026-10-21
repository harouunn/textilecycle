<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Front office
Route::get('/', AccueilController::class)->name('home');

// Back office
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/count', [NotificationController::class, 'unreadCount'])->name('count');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('readAll');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/administration.php';

// Modules (one file per module, edited only by its team)
require __DIR__.'/modules/depot.php';
require __DIR__.'/modules/ateliers.php';
require __DIR__.'/modules/upcycling.php';
require __DIR__.'/modules/dons.php';
