<?php

namespace Tests\Feature;

use App\Models\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the logout flow.
 *
 * The logout route used to be POST-only while both portal headers rendered a
 * plain GET anchor, so clicking "Logout" returned a 405 Method Not Allowed.
 * The headers now submit a POST form and the route accepts GET as a robust
 * fallback, so logging out always works.
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $store = Store::factory()->create();

        return User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
        ]);
    }

    public function test_post_logout_invalidates_the_session_and_redirects_to_login(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_get_logout_is_also_tolerated_as_a_fallback(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}