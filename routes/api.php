<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public API routes
// ---------------------------------------------------------------------------

// POST /api/login — issues a Sanctum bearer token plus the user profile.
// Payload: { "email": "...", "password": "..." }
Route::post('/login', [AuthController::class, 'login']);

// ---------------------------------------------------------------------------
// Sanctum protected API routes (used by the Flutter app in Phase 2)
//
// Authenticate with:
//   Authorization: Bearer {id}|{plainTextToken}
// returned by POST /api/login.
// ---------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    // GET /api/menu — categories + food items for the current store.
    Route::get('/menu', [MenuController::class, 'index']);

    // POST /api/orders — accepts the Flutter cart payload and saves the order
    // through the same CheckoutService used by the web POS.
    Route::post('/orders', [OrderController::class, 'store']);

    // GET /api/user — convenience endpoint returning the authenticated profile.
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});