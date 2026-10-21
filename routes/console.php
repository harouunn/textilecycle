<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Module Dépôt : chaque matin, expiration des réservations non retirées et rappels de la veille.
// En local : php artisan schedule:work (ou lancer directement php artisan depot:reservations).
Schedule::command('depot:reservations')->dailyAt('08:00')->withoutOverlapping();

// Module Associations & dons : chaque nuit, les campagnes de collecte dont la date de fin est passée sont terminées.
Schedule::command('dons:cloturer-campagnes')->dailyAt('00:10')->withoutOverlapping();
