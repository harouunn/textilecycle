<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve tout le back office (/admin et /admin/...) aux administrateurs.
 * Ajouté au groupe « web » : il protège les routes admin de tous les modules sans modifier leurs fichiers.
 * Un visiteur non connecté est laissé au middleware « auth », qui le renvoie vers la connexion.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*') && $request->user() && ! $request->user()->isAdmin()) {
            abort(403, 'Cet espace est réservé aux administrateurs.');
        }

        return $next($request);
    }
}
