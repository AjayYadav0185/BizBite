<?php

use App\Http\Controllers\AppDownloadController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Pos\BillingDashboard;
use App\Livewire\Pos\OrderQueue;
use App\Livewire\Pos\ShiftPanel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    // Route each role to its home portal: owners land on the admin console,
    // cashiers land straight on the POS billing screen.
    return redirect()->route(
        Auth::user()->isAdmin() ? 'admin.dashboard' : 'pos.billing'
    );
})->name('home');

// ---------------------------------------------------------------------------
// Public app distribution (/download-app)
//
// Landing page + download endpoint for the latest mobile build. The download
// URL is permanent — each new APK dropped into storage/app/public/apks is
// picked up automatically (see scripts/deploy-apk.sh and
// AppDownloadController::latestApk()).
// ---------------------------------------------------------------------------

Route::get('/download-app', [AppDownloadController::class, 'page'])->name('download-app');
Route::get('/download-app/download', [AppDownloadController::class, 'download'])->name('download-app.download');

// ---------------------------------------------------------------------------
// Authentication (shared by both portals)
// ---------------------------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// ---------------------------------------------------------------------------
// Protected portals
//
// Authorization is backed by the role Gates declared in AppServiceProvider
// ('admin' = owner, 'cashier' = staff) and enforced by the 'role' middleware
// alias registered in bootstrap/app.php. The Livewire components additionally
// re-check the gates in mount()/checkout() as defense in depth.
// ---------------------------------------------------------------------------

Route::middleware(['auth'])->group(function () {
    Route::match(['get', 'post'], '/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    // STAFF POS PORTAL — high-speed billing (admin OR cashier).
    Route::get('/pos', BillingDashboard::class)
        ->middleware('role:admin,cashier')
        ->name('pos.billing');

    // STAFF POS PORTAL — kitchen/counter order queue (§4.4/§4.5). Live board
    // for advancing bills through pending → preparing → ready → completed.
    Route::get('/pos/orders', OrderQueue::class)
        ->middleware('role:admin,cashier')
        ->name('pos.orders');

    // STAFF POS PORTAL — shift / cash-drawer panel (staff shifts).
    Route::get('/pos/shift', ShiftPanel::class)
        ->middleware('role:admin,cashier')
        ->name('pos.shift');

    // OWNER ADMIN PORTAL — business setup & telemetry (admin only).
    Route::get('/admin', AdminDashboard::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');
});
