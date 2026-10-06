<?php

use App\Exceptions\InvalidTransitionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Session nécessaire pour l'authentification de l'agent sur l'API :
        // POST /api/admin/login pose un marqueur de session que le
        // middleware "admin" vérifie sur les endpoints réservés.
        $middleware->api(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Une transition de cycle de vie interdite est une erreur MÉTIER,
        // pas une erreur de validation : elle est rendue en HTTP 409 Conflict.
        $exceptions->render(function (InvalidTransitionException $e, \Illuminate\Http\Request $httpRequest) {
            if ($httpRequest->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 409);
            }
        });
    })->create();
