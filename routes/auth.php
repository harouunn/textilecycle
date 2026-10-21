<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\DefiDeuxFacteursController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SecuriteController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Image CAPTCHA (anti-robot) des formulaires de connexion et d'inscription
Route::get('captcha', CaptchaController::class)->middleware('throttle:30,1')->name('captcha');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    // Envoie un e-mail : limité par adresse IP (voir AppServiceProvider, limiteur « envoi-emails »).
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:envoi-emails');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:envoi-emails')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Seconde étape de connexion pour les comptes protégés par la double authentification
    Route::get('connexion/code', [DefiDeuxFacteursController::class, 'create'])->name('deux-facteurs.create');
    Route::post('connexion/code', [DefiDeuxFacteursController::class, 'store'])->name('deux-facteurs.store');
});

// Sécurité du compte : double authentification par QR code
Route::middleware('auth')->prefix('compte/securite')->name('securite.')->group(function () {
    Route::get('/', [SecuriteController::class, 'show'])->name('show');
    Route::post('activer', [SecuriteController::class, 'activer'])->name('activer');
    Route::post('confirmer', [SecuriteController::class, 'confirmer'])->name('confirmer');
    Route::post('codes', [SecuriteController::class, 'regenererCodes'])->name('codes');
    Route::delete('/', [SecuriteController::class, 'desactiver'])->name('desactiver');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    // Vérification par code à 6 chiffres reçu par e-mail
    Route::post('verify-email', VerifyEmailController::class)
        ->middleware('throttle:10,1')
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
