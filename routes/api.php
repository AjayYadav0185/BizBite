<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuAdminController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OpsAdminController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\OpsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WalletRechargeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public API routes
// ---------------------------------------------------------------------------

// POST /api/login — issues a Sanctum bearer token plus the user profile.
// Payload: { "email": "...", "password": "..." }
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

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

    // GET /api/orders — today's kitchen/counter queue for the caller's store
    // (status transitions are shared with the web OrderQueue board).
    Route::get('/orders', [OrderApiController::class, 'index'])
        ->middleware('role:admin,cashier');

    // PATCH /api/orders/{order}/status — move a bill through the fulfilment
    // flow (pending → preparing → ready → completed, or cancelled).
    Route::patch('/orders/{order}/status', [OrderApiController::class, 'updateStatus'])
        ->middleware('role:admin,cashier');

    // POST /api/orders/{order}/refund — partial refund with reason (ledger +
    // negative payment leg + audit row, never exceeding the bill total).
    Route::post('/orders/{order}/refund', [OpsController::class, 'refund'])
        ->middleware('role:admin,cashier');

    // PATCH /api/orders/{order}/delivery — delivery workflow moves.
    Route::patch('/orders/{order}/delivery', [OpsController::class, 'delivery'])
        ->middleware('role:admin,cashier');

    // Shifts: open / close / list (cash-drawer sessions).
    Route::get('/shifts', [OpsController::class, 'shifts'])->middleware('role:admin,cashier');
    Route::post('/shifts/open', [OpsController::class, 'openShift'])->middleware('role:admin,cashier');
    Route::post('/shifts/{shift}/close', [OpsController::class, 'closeShift'])->middleware('role:admin,cashier');

    // Owner reports: hourly / best-sellers / range. Staff can read the day
    // numbers too; CSV export stays admin-gated via the controller.
    Route::get('/reports/hourly', [OpsController::class, 'hourly'])->middleware('role:admin,cashier');
    Route::get('/reports/best-sellers', [OpsController::class, 'bestSellers'])->middleware('role:admin,cashier');
    Route::get('/reports/range', [OpsController::class, 'range'])->middleware('role:admin,cashier');

    // Owner-managed resources for the Flutter console + admin tabs.
    Route::get('/tables', [OpsController::class, 'tables'])->middleware('role:admin,cashier');
    Route::get('/campaigns', [OpsAdminController::class, 'campaigns'])->middleware('role:admin,cashier');

    // Admin-only menu writes for the Flutter Store console.
    // Reads stay on GET /api/menu; writes mirror Livewire MenuManager
    // validation and are tenant-scoped via the global StoreScope.
    Route::middleware('role:admin')->group(function () {
        Route::post('/categories', [MenuAdminController::class, 'storeCategory']);
        Route::put('/categories/{id}', [MenuAdminController::class, 'updateCategory'])->whereNumber('id');
        Route::post('/menu/items', [MenuAdminController::class, 'storeItem']);
        Route::put('/menu/items/{id}', [MenuAdminController::class, 'updateItem'])->whereNumber('id');
        Route::delete('/menu/items/{id}', [MenuAdminController::class, 'destroyItem'])->whereNumber('id');

        // Owner-managed tables / campaigns / staff (admin console + app).
        Route::post('/tables', [OpsAdminController::class, 'storeTable']);
        Route::patch('/tables/{table}', [OpsAdminController::class, 'updateTable']);
        Route::delete('/tables/{table}', [OpsAdminController::class, 'destroyTable']);
        Route::post('/campaigns', [OpsAdminController::class, 'storeCampaign']);
        Route::patch('/campaigns/{campaign}', [OpsAdminController::class, 'updateCampaign']);
        Route::delete('/campaigns/{campaign}', [OpsAdminController::class, 'destroyCampaign']);
        Route::get('/staff', [OpsAdminController::class, 'staff']);
        Route::post('/staff', [OpsAdminController::class, 'storeStaff']);
        Route::patch('/staff/{user}', [OpsAdminController::class, 'updateStaff']);
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

    // Store profile & branding ("About shop" / "Manage shop" on My Profile).
    // Every signed-in staff member can READ their own store's shop details;
    // only the owner (admin) can change them — mirroring the web Receipt
    // Customizer. The store is always resolved from the caller's own user
    // row, so no tenant id is accepted from the client.
    Route::get('/store', [StoreController::class, 'show'])
        ->middleware('role:admin,cashier');

    Route::middleware('role:admin')->group(function () {
        Route::put('/store', [StoreController::class, 'update']);

        // Multipart logo upload (jpg/jpeg/png/webp, max 2 MB) + removal.
        Route::post('/store/logo', [StoreController::class, 'uploadLogo']);
        Route::delete('/store/logo', [StoreController::class, 'destroyLogo']);
    });

    // Customer Wallet (both staff roles — the wallet belongs to the caller).
    Route::get('/wallet/balance', [WalletController::class, 'balance'])
        ->middleware('role:admin,cashier');
    Route::post('/wallet/recharge/initiate', [WalletRechargeController::class, 'initiate'])
        ->middleware('role:admin,cashier');
    Route::post('/wallet/recharge/verify', [WalletRechargeController::class, 'verify'])
        ->middleware('role:admin,cashier');

    // Spec alias: POST /api/bill/generate behaves exactly like POST
    // /api/orders (bill + 1% wallet deduction in one transaction).
    Route::post('/bill/generate', [OrderApiController::class, 'store'])
        ->middleware('role:admin,cashier');
});
