<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnTetesSecurite;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hébergeur derrière un proxy ou un load balancer : HTTPS et IP du visiteur bien détectés.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            EnsureUserIsAdmin::class,
            EnTetesSecurite::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
