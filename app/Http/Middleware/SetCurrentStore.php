<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

/**
 * Resolve the authenticated user's store and expose it through the request
 * scoped `Context` facade.
 *
 * Works uniformly for:
 *   - Web requests: the Sanctum guard falls back to the session `web` guard.
 *   - API requests: the Sanctum guard resolves the bearer token.
 *
 * The global `StoreScope` / `OrderStoreScope` read `Context::current_store_id`
 * so every business query is automatically tenant scoped per request.
 */
class SetCurrentStore
{
    /**
     * Handle the request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Ask Sanctum's guard first: it transparently checks the session guard
        // ("web") and then falls back to the Authorization bearer token.
        $user = Auth::guard('sanctum')->user();

        // ALWAYS set (not just add) the tenant context, so a value left over
        // from a previous request can never leak into this one. Without the
        // reset, an unauthenticated request — or a queued job / test run that
        // skips the middleware — would silently inherit the LAST request's
        // store_id and see that store's data through StoreScope.
        Context::add('current_store_id', $user?->store_id);

        return $next($request);
    }
}
