<?php

namespace Tests\Feature;

use App\Livewire\Pos\BillingDashboard;
use App\Models\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the Staff POS portal.
 *
 * The key one here is the checkout-after-interaction flow: Livewire 3 only
 * calls mount() once; every subsequent wire:click re-hydrates the component.
 * The OrderService must be re-bound in boot() or "Typed property
 * BillingDashboard::$orders must not be accessed before initialization" was
 * thrown the moment a cashier tapped an item and then pressed settle.
 */
class PosBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_add_items_and_settle_a_bill(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
        ]);
        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'price' => 100.50,
            'is_available' => true,
        ]);

        Livewire::actingAs($user)
            ->test(BillingDashboard::class)
            ->call('addItem', $item->id)              // hydration #1
            ->call('incrementQuantity', $item->id)    // hydration #2
            ->call('checkout', 'cash')                // hydration #3 — used to crash here
            ->assertDispatched('trigger-print')
            ->assertSet('cart', [])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tbl_pos_orders', [
            'store_id' => $store->id,
            'user_id' => $user->id,
            'total_amount' => 201.00,
            'payment_mode' => 'cash',
        ]);

        $order = Order::query()->withoutGlobalScopes()->firstWhere('store_id', $store->id);
        $this->assertNotNull($order);
        $this->assertSame(2, $order->items()->sum('quantity'));
    }

    public function test_settling_with_an_empty_cart_shows_an_error_and_creates_no_order(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Cashier,
        ]);

        Livewire::actingAs($user)
            ->test(BillingDashboard::class)
            ->call('checkout', 'cash')
            ->assertSet('error', 'Cart is empty — add items before settling.')
            ->assertNotDispatched('trigger-print');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_admin_can_reach_the_pos_portal(): void
    {
        $store = Store::factory()->create();
        $admin = User::factory()->create([
            'store_id' => $store->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->get(route('pos.billing'))
            ->assertOk();
    }
}