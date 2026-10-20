<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité ajoutés à chaque page :
 *  - le site ne peut pas être affiché dans une iframe d'un autre site (anti-clickjacking) ;
 *  - le navigateur ne devine pas le type des fichiers (anti-MIME sniffing) ;
 *  - l'adresse complète n'est pas transmise aux sites externes ;
 *  - caméra, micro et géolocalisation sont désactivés ;
 *  - en HTTPS, le navigateur retient de toujours utiliser HTTPS (HSTS).
 */
class EnTetesSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
