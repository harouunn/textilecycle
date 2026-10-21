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
use App\Http\Controllers\Dons\Admin\CampagneController as AdminCampagneController;
use App\Http\Controllers\Dons\Admin\DonController as AdminDonController;
use App\Http\Controllers\Dons\Admin\JournalIaController;
use App\Http\Controllers\Dons\Admin\TableauDeBordController;
use App\Http\Controllers\Dons\AssociationController;
use App\Http\Controllers\Dons\DonController;
use App\Http\Controllers\Dons\EspaceAssociationController;
use App\Http\Controllers\Dons\EspaceCreneauController;
use App\Http\Controllers\Dons\EspacePresentationController;
use App\Http\Controllers\Dons\IaController;
use App\Http\Controllers\Dons\ImpactController;
use App\Http\Controllers\Dons\NotificationController;
use App\Http\Controllers\Dons\VerificationRecuController;
use Illuminate\Support\Facades\Route;

// Front office
Route::prefix('dons')->name('dons.')->group(function () {
    Route::get('/', [AssociationController::class, 'index'])->name('index');
    Route::get('/associations/{association}', [AssociationController::class, 'show'])->name('associations.show');
    Route::get('/campagnes', [DonController::class, 'campagnes'])->name('campagnes.index');
    Route::get('/classement', [ImpactController::class, 'classement'])->name('classement');
    // Page ouverte par le QR code des reçus : publique, mais limitée pour empêcher de deviner les références.
    Route::get('/verifier/{reference}', VerificationRecuController::class)->name('verifier')
        ->where('reference', 'DON-\d{4}-\d{6,}')
        ->middleware('throttle:30,1,dons-verifier');

    // Fonctionnalités IA : 10 requêtes par minute et par compte (par adresse IP pour un visiteur), toutes confondues.
    Route::prefix('ia')->name('ia.')->middleware('throttle:10,1,dons-ia')->group(function () {
        Route::post('/assistant', [IaController::class, 'assistant'])->name('assistant');
        Route::delete('/assistant', [IaController::class, 'oublierAssistant'])->name('assistant.oublier');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::post('/analyser-photo', [IaController::class, 'analyserPhoto'])->name('analyser-photo');
            Route::post('/recommandations', [IaController::class, 'recommandations'])->name('recommandations');
            Route::post('/rediger', [IaController::class, 'rediger'])->name('rediger');
        });
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/associations/{association}/faire-un-don', [DonController::class, 'create'])->name('create');
        // 5 propositions par minute et par compte (compteur propre à ce formulaire).
        Route::post('/associations/{association}/faire-un-don', [DonController::class, 'store'])->name('store')
            ->middleware('throttle:5,1,dons-store');
        Route::get('/mes-dons', [DonController::class, 'index'])->name('mes-dons');
        Route::get('/mes-dons/{don}', [DonController::class, 'show'])->name('mes-dons.show');
        Route::get('/mes-dons/{don}/recu', [DonController::class, 'recu'])->name('mes-dons.recu');
        Route::patch('/mes-dons/{don}/annuler', [DonController::class, 'cancel'])->name('cancel');
        Route::get('/mon-impact', [ImpactController::class, 'monImpact'])->name('mon-impact');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/tout-lire', [NotificationController::class, 'toutLire'])->name('notifications.tout-lire');
        Route::get('/notifications/{id}', [NotificationController::class, 'ouvrir'])->name('notifications.ouvrir');

        // Espace association (responsable d'une association)
        Route::get('/espace', [EspaceAssociationController::class, 'index'])->name('espace.index');
        Route::get('/espace/dons/{don}', [EspaceAssociationController::class, 'show'])->name('espace.show');
        Route::patch('/espace/dons/{don}/statut', [EspaceAssociationController::class, 'transition'])->name('espace.statut');
        Route::get('/espace/creneaux', [EspaceCreneauController::class, 'index'])->name('espace.creneaux.index');
        Route::post('/espace/creneaux', [EspaceCreneauController::class, 'store'])->name('espace.creneaux.store');
        Route::delete('/espace/creneaux/{creneau}', [EspaceCreneauController::class, 'destroy'])->name('espace.creneaux.destroy');
        Route::get('/espace/associations/{association}/presentation', [EspacePresentationController::class, 'edit'])->name('espace.presentation.edit');
        Route::put('/espace/associations/{association}/presentation', [EspacePresentationController::class, 'update'])->name('espace.presentation.update');
        Route::get('/espace/associations/{association}/rapport', [EspacePresentationController::class, 'rapport'])->name('espace.rapport');
    });
});

// Back office
Route::middleware('auth')->prefix('admin/dons')->name('admin.dons.')->group(function () {
    Route::get('/', TableauDeBordController::class)->name('tableau-de-bord');
    Route::get('journal-ia', JournalIaController::class)->name('journal-ia');
    Route::resource('associations', AdminAssociationController::class);
    Route::resource('campagnes', AdminCampagneController::class);
    Route::get('dons/export/{format}', [AdminDonController::class, 'export'])->name('dons.export')->whereIn('format', ['xlsx', 'csv']);
    Route::patch('dons/{don}/statut', [AdminDonController::class, 'updateStatut'])->name('dons.statut');
    Route::resource('dons', AdminDonController::class);
});
