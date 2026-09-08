<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Role middleware alias ('role:admin', 'role:cashier').
 *
 * Backed by the role gates declared in AppServiceProvider so that both the
 * web routes and any future non-Livewire surfaces share one authorization
 * vocabulary:
 *
 *   Route::middleware('role:admin')->group(...);        // owner only
 *   Route::middleware('role:admin,cashier')->group(...); // staff POS
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$roles  Any of 'admin' or 'cashier'.
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        abort_if($user === null, 401);

        abort_unless(
            in_array($user->role->value, $roles, strict: true),
            403,
            'Your role does not have access to this area.'
        );

        return $next($request);
    }
}
