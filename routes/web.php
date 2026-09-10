<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Pos\BillingDashboard;
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

    // OWNER ADMIN PORTAL — business setup & telemetry (admin only).
    Route::get('/admin', AdminDashboard::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');
});
