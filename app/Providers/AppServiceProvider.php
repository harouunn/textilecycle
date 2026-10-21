<?php

namespace App\Providers;

use App\Models\DemandeReparation;
use App\Observers\DemandeReparationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Formulaires qui envoient un e-mail sans être connecté (inscription, mot de passe oublié) :
        // 3 par minute et 10 par heure pour une même adresse IP, pour ne pas épuiser la boîte d'envoi Gmail.
        RateLimiter::for('envoi-emails', fn (Request $request) => [
            Limit::perMinute(3)->by('minute:'.$request->ip()),
            Limit::perHour(10)->by('heure:'.$request->ip()),
        ]);

        // En ligne (APP_URL en https), tous les liens et formulaires générés utilisent HTTPS.
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Module Ateliers : notifications et analyse IA à chaque nouvelle demande de réparation.
        DemandeReparation::observe(DemandeReparationObserver::class);
    }
}
