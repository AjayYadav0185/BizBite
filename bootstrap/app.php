<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetCurrentStore;
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
    ->withMiddleware(function (Middleware $middleware) {
        // NOTE: do NOT use $middleware->use([...]) here — it REPLACES the
        // entire Laravel 11 global stack (TrustProxies, HandleCors,
        // PreventRequestsDuringMaintenance, ValidatePostSize, TrimStrings,
        // ConvertEmptyStringsToNull). That silently disabled CORS, which is
        // why Flutter Web on Chrome failed every /api/* call (OPTIONS
        // preflight got no Access-Control-Allow-Origin headers) while
        // Android/iOS (which ignore CORS) kept working.
        // append() keeps the defaults and adds ours on top.
        $middleware->append([
            SetCurrentStore::class,
        ]);

        // Role-based route protection: 'role:admin', 'role:admin,cashier'.
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
