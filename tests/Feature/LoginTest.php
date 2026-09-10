<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the web sign-in flow.
 *
 * The API controller already rejected deactivated accounts, but the Livewire
 * web login skipped that check — a disabled cashier could keep signing in and
 * placing bills through the POS.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_cannot_sign_in_via_the_web(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'store_id' => $store->id,
            'email' => 'blocked@example.test',
            'password' => 'password',
            'role' => UserRole::Cashier,
            'is_active' => false,
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_cashier_is_routed_to_the_pos_dashboard(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'store_id' => $store->id,
            'email' => 'cashier@example.test',
            'password' => 'password',
            'role' => UserRole::Cashier,
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('pos.billing'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_admin_is_routed_to_the_admin_dashboard(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'store_id' => $store->id,
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}