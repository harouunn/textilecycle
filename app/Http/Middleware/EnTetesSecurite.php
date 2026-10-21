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
 *  - caméra et micro sont désactivés, la géolocalisation est réservée aux pages du site ;
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
        // Géolocalisation autorisée pour le site lui-même seulement (filtre « près de moi » du module Dons ;
        // le navigateur demande toujours l'accord du visiteur). Interdite aux iframes d'autres sites.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
