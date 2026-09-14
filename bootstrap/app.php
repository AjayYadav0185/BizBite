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
        // Expired-session logout: when a user leaves a POS/admin screen open
        // past SESSION_LIFETIME and then clicks Logout, the POST carries a
        // stale CSRF token. Without this handler they got a raw "419 Page
        // Expired" error instead of a clean bounce to the login screen.
        //
        // NOTE: before render callbacks run, Laravel wraps the original
        // TokenMismatchException in HttpException(419) (see Handler::
        // prepareException), so we must intercept the HttpException and
        // check its status + previous exception.
        // Livewire/AJAX/JSON requests are left untouched so those clients
        // keep their own retry flows.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if (
                $e->getStatusCode() !== 419
                || ! $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException
            ) {
                return null;
            }

            if ($request->expectsJson() || $request->header('X-Livewire')) {
                return null;
            }

            return redirect()
                ->route('login')
                ->with('status', 'Your session expired. Please sign in again.');
        });
    })->create();
