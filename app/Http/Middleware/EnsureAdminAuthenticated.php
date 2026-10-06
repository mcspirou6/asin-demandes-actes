<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protège les endpoints réservés à l'agent connecté (espace validateur).
 * L'authentification repose sur la session (voir AdminController::login).
 * Sans session valide : HTTP 401, l'usager ne peut pas traiter de demandes.
 */
class EnsureAdminAuthenticated
{
    public function handle(HttpRequest $httpRequest, Closure $next): Response
    {
        if (! $httpRequest->session()->get('admin_authenticated')) {
            return response()->json([
                'message' => "Accès réservé à l'agent connecté.",
            ], 401);
        }

        return $next($httpRequest);
    }
}
