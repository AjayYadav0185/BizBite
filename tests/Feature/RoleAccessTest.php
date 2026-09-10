<?php

namespace Tests\Feature;

use App\Models\Enums\UserRole;
use App\Http\Middleware\EnsureRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Regression tests for role-based route protection.
 *
 * Previously, when a user's role was changed in the database to a value that
 * is not part of the UserRole enum (e.g. 'staff' instead of 'cashier'), the
 * enum cast returned null and EnsureRole crashed with "Attempt to read
 * property 'value' on null". Access is now denied gracefully with a 403, and
 * deactivated accounts are blocked by the middleware too.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_protected_portals(): void
    {
        $this->get(route('pos.billing'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_cashier_can_use_the_pos_but_not_the_admin_portal(): void
    {
        $store = Store::factory()->create();
        $cashier = User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
        ]);

        $this->actingAs($cashier)->get(route('pos.billing'))->assertOk();
        $this->actingAs($cashier)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_manager_changed_to_unknown_role_is_denied_not_crashed(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
        ]);

        // Simulate an owner hand-editing the DB to a value that no longer
        // exists in the UserRole enum (e.g. 'staff' instead of 'cashier').
        // SQLite enforces the enum CHECK, so we emulate the medium (e.g.
        // MySQL non-strict mode silently truncating to '') by putting the
        // raw value into the model's attributes — the cast then resolves it
        // to null, which is exactly what the middleware must survive.
        $user->setRawAttributes(array_merge(
            $user->getAttributes(),
            ['role' => 'staff']
        ));

        $request = Request::create(route('pos.billing'), 'GET');
        $request->setUserResolver(fn (): User => $user);

        $middleware = new EnsureRole();
        try {
            $middleware->handle($request, fn ($request) => response('ok'), 'admin', 'cashier');
            $this->fail('Expected a 403 for a user with an unknown role.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_deactivated_account_is_blocked_by_the_middleware(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
            'is_active' => false,
        ]);

        $this->actingAs($user)->get(route('pos.billing'))->assertForbidden();
    }
}