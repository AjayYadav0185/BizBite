<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuAdminController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public API routes
// ---------------------------------------------------------------------------

// POST /api/login — issues a Sanctum bearer token plus the user profile.
// Payload: { "email": "...", "password": "..." }
Route::post('/login', [AuthController::class, 'login']);

// ---------------------------------------------------------------------------
// Sanctum protected API routes (consumed by the Flutter app in Phase 2)
//
// Authenticate with:
//   Authorization: Bearer {id}|{plainTextToken}
// returned by POST /api/login.
//
// TENANCY: the global SetCurrentStore middleware (registered application-
// wide) populates the request Context with the authenticated user's store_id,
// so every scoped model query below is automatically constrained to the
// caller's store — no store_id is ever accepted from the client.
//
// AUTHORIZATION: the 'role' alias (App\Http\Middleware\EnsureRole) reuses the
// exact same role vocabulary as the web portals: 'admin' = owner, 'cashier'.
// ---------------------------------------------------------------------------

Route::middleware(['auth:sanctum'])->group(function () {
    // GET /api/user — authenticated profile (role, store_id, etc.).
    Route::get('/user', fn (Request $request) => $request->user());

    // GET /api/menu — active categories + available food items for the
    // caller's store. Feeds the Flutter ordering screen.
    Route::get('/menu', [MenuController::class, 'index'])
        ->middleware('role:admin,cashier');

    // POST /api/orders — accepts the Flutter cart payload and settles the
    // bill through the EXACT SAME OrderService used by the Livewire POS
    // (transaction + server-side price snapshotting + per-day bill number).
    Route::post('/orders', [OrderApiController::class, 'store'])
        ->middleware('role:admin,cashier');

    // Admin-only menu writes for the Flutter Store console.
    // Reads stay on GET /api/menu; writes mirror Livewire MenuManager
    // validation and are tenant-scoped via the global StoreScope.
    Route::middleware('role:admin')->group(function () {
        Route::post('/categories', [MenuAdminController::class, 'storeCategory']);
        Route::put('/categories/{id}', [MenuAdminController::class, 'updateCategory'])->whereNumber('id');
        Route::post('/menu/items', [MenuAdminController::class, 'storeItem']);
        Route::put('/menu/items/{id}', [MenuAdminController::class, 'updateItem'])->whereNumber('id');
        Route::delete('/menu/items/{id}', [MenuAdminController::class, 'destroyItem'])->whereNumber('id');
    });

    // ANY /api/logout — revokes the token used for the current request.
    // Accepts GET/POST/DELETE so Flutter, browsers and API clients never
    // hit a 405 "Method Not Allowed" at logout time.
    Route::match(['get', 'post', 'delete'], '/logout', [AuthController::class, 'logout']);

    // Self-service profile management (own details only — never role /
    // store_id / email, which stay owner-controlled like the web portal).
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'password']);
});
