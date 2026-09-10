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

        abort_unless($user->is_active, 403, 'Your account has been deactivated.');

        // The role cast may resolve to null if the user's stored role value is
        // not part of the UserRole enum (e.g. an owner edited the column to a
        // value such as 'staff' directly in the database). Use a null-safe read
        // and deny access instead of crashing on a null property access.
        $role = $user->role?->value;

        abort_unless(
            is_string($role) && in_array($role, $roles, strict: true),
            403,
            'Your role does not have access to this area.'
        );

        return $next($request);
    }
}
