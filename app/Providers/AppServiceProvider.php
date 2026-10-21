<?php

namespace App\Providers;

use App\Models\DemandeReparation;
use App\Observers\DemandeReparationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DemandeReparation::observe(DemandeReparationObserver::class);
    }
}
