<?php

namespace Tests\Feature;

use App\Models\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression tests for the Admin <-> POS portal switching and the logout
 * flow from every portal screen.
 *
 * Previously:
 *   - The POS billing header had NO link back to the Admin console, so an
 *     owner who switched to POS mode was stuck (only Order Queue had the
 *     "Admin" link).
 *   - The Shift panel header had neither an "Admin" link nor a Logout
 *     button, so staff could not sign out from that screen at all.
 *   - Clicking Logout after the session expired (page left open past
 *     SESSION_LIFETIME) produced a raw "419 Page Expired" error instead of
 *     a clean redirect to the login screen.
 */
class PortalSwitchLogoutTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private function admin(): User
    {
        $store = Store::factory()->create();

        return User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Admin,
            'name' => 'Owner Admin',
        ]);
    }

    private function cashier(): User
    {
        $store = Store::factory()->create();

        return User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
            'name' => 'Counter Cashier',
        ]);
    }

    public function test_admin_can_switch_between_admin_and_pos_portals_both_ways(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();

        // Hop into POS mode...
        $this->get(route('pos.billing'))->assertOk();

        // ...and back out again.
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_pos_billing_header_offers_admin_link_to_admins_only(): void
    {
        $admin = $this->admin();
        $cashier = $this->cashier();

        $this->actingAs($admin)
            ->get(route('pos.billing'))
            ->assertOk()
            ->assertSee(route('admin.dashboard'));

        $this->actingAs($cashier)
            ->get(route('pos.billing'))
            ->assertOk()
            ->assertDontSee(route('admin.dashboard'));
    }

    public function test_shift_panel_offers_admin_link_and_logout_for_admins(): void
    {
        $admin = $this->admin();
        $cashier = $this->cashier();

        $this->actingAs($admin)
            ->get(route('pos.shift'))
            ->assertOk()
            ->assertSee(route('admin.dashboard'))
            ->assertSee(route('logout'));

        $this->actingAs($cashier)
            ->get(route('pos.shift'))
            ->assertOk()
            ->assertSee(route('logout'))
            ->assertDontSee(route('admin.dashboard'));
    }

    public function test_logout_from_the_pos_portal_ends_the_session(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('pos.billing'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();

        // After signing out the POS portal is no longer reachable.
        $this->get(route('pos.billing'))->assertRedirect(route('login'));
    }

    public function test_expired_session_logout_redirects_to_login_instead_of_419(): void
    {
        Route::get('/_force-token-mismatch', fn (): never => throw new TokenMismatchException('CSRF token expired.'));

        // Browser (non-JSON) request: bounce to the login screen with a
        // friendly notice instead of the raw "419 Page Expired" error page.
        $this->get('/_force-token-mismatch')
            ->assertRedirect(route('login'));

        $this->get(route('login'))->assertSee('Your session expired. Please sign in again.');
    }

    public function test_expired_session_keeps_419_for_json_and_livewire_clients(): void
    {
        Route::get('/_force-token-mismatch-json', fn (): never => throw new TokenMismatchException('CSRF token expired.'));

        // API/Livewire clients must keep receiving the machine-readable 419.
        $this->getJson('/_force-token-mismatch-json')->assertStatus(419);
    }
}
