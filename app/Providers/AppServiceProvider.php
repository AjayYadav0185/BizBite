<?php

namespace App\Providers;

use App\Models\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The OrderService is a stateless, single-purpose application service.
        // Binding it as a singleton lets both the Staff POS Livewire component
        // and the Sanctum OrderApiController resolve the exact same instance
        // (and therefore the same checkout code path) with zero duplication.
        $this->app->singleton(OrderService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // All framework + business tables use the `tbl_` prefix — point
        // Sanctum's token model at the renamed table.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        // -------------------------------------------------------------------
        // Role Gates
        //
        // BizBite has exactly two roles: 'admin' (owner) and 'cashier'
        // (staff). The gates below are the single source of truth for the
        // architectural separation between the Owner Admin Portal and the
        // Staff POS Portal.
        //
        // They are consumed by:
        //   - Route middleware ('can:access-pos-portal', 'can:access-admin-portal')
        //   - Defense-in-depth checks inside Livewire components
        //     (Gate::authorize / $this->authorize) and API controllers
        // -------------------------------------------------------------------

        // Any authenticated staff member (admin OR cashier) may bill orders.
        Gate::define('access-pos-portal', fn (User $user): bool => true);

        // Only store owners may reach business setup & telemetry screens.
        Gate::define('access-admin-portal', fn (User $user): bool => $user->role === UserRole::Admin);

        // Fine grained gate: only admins may mutate the menu or store setup.
        Gate::define('manage-menu', fn (User $user): bool => $user->role === UserRole::Admin);

        // Any staff member may settle bills (used by OrderService callers).
        Gate::define('place-orders', fn (User $user): bool => $user->role === UserRole::Admin
            || $user->role === UserRole::Cashier
        );
    }
}
