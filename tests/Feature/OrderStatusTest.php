<?php

namespace Tests\Feature;

use App\Livewire\Pos\OrderQueue;
use App\Models\AuditLog;
use App\Models\Enums\OrderStatus;
use App\Models\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Order status flow — priority feature §5 (plan §4.4 / §4.5).
 *
 * The checkout settles and prints the bill, then hands it to the kitchen as a
 * NEW order. This suite locks in that lifecycle:
 *   1. A freshly settled bill starts as `pending` (payment already `paid`).
 *   2. Staff advance it pending → preparing → ready → completed from the board.
 *   3. Illegal moves are refused (cancelled is terminal).
 *   4. Cancelling voids the bill, returns tracked stock and writes an audit row.
 *   5. The board separates dine-in from takeaway (§4.5).
 *   6. The Sanctum API exposes the same transitions, tenant-isolated.
 */
class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private function staff(Store $store, UserRole $role = UserRole::Cashier): User
    {
        return User::factory()->create([
            'store_id' => $store->id,
            'role' => $role,
        ]);
    }

    /** Settle a real bill through the shared OrderService and return the order. */
    private function placeOrder(Store $store, User $user, string $orderType = 'takeaway', ?int $stock = null): Order
    {
        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'price' => 100.00,
            'is_available' => true,
            'stock_quantity' => $stock,
        ]);

        return app(OrderService::class)->place($user, [
            'items' => [['food_item_id' => $item->id, 'quantity' => 1]],
            'payment_mode' => 'cash',
            'order_type' => $orderType,
        ])->order;
    }

    public function test_a_new_bill_starts_as_a_pending_paid_order(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);

        $order = $this->placeOrder($store, $user);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertDatabaseHas('tbl_pos_orders', [
            'id' => $order->id,
            'status' => 'pending',
            'payment_status' => 'paid',
        ]);
    }

    public function test_cashier_can_advance_a_bill_through_the_fulfilment_flow(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $order = $this->placeOrder($store, $user);

        $component = Livewire::actingAs($user)->test(OrderQueue::class);

        $component->call('advance', $order->id)->assertHasNoErrors();
        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);

        $component->call('advance', $order->id);
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);

        $component->call('advance', $order->id);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_an_illegal_transition_is_refused(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $order = $this->placeOrder($store, $user);

        app(OrderService::class)->updateStatus($user, $order, OrderStatus::Cancelled);

        // Cancelled is terminal: even the service refuses to move it again.
        $this->expectException(OrderPlacementException::class);

        app(OrderService::class)->updateStatus($user, $order->fresh(), OrderStatus::Preparing);
    }

    public function test_cancelling_a_bill_voids_it_restores_stock_and_is_audited(): void
    {
        $store = Store::factory()->create();
        $admin = $this->staff($store, UserRole::Admin);
        $order = $this->placeOrder($store, $admin, stock: 10);

        /** @var FoodItem $item */
        $item = FoodItem::query()->withoutGlobalScopes()->where('store_id', $store->id)->firstOrFail();
        $this->assertSame(9, $item->fresh()->stock_quantity);   // sold 1

        Livewire::actingAs($admin)
            ->test(OrderQueue::class)
            ->call('cancelOrder', $order->id)
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(10, $item->fresh()->stock_quantity);  // returned to the shelf

        $this->assertDatabaseHas('tbl_pos_audit_logs', [
            'store_id' => $store->id,
            'action' => AuditLog::ACTION_ORDER_CANCELLED,
            'entity_id' => $order->id,
        ]);
    }

    public function test_the_board_surfaces_an_illegal_move_as_a_banner(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $order = $this->placeOrder($store, $user);

        app(OrderService::class)->updateStatus($user, $order, OrderStatus::Cancelled);

        Livewire::actingAs($user)
            ->test(OrderQueue::class)
            ->call('advance', $order->id)
            ->assertSet('error', 'Bill '.$order->order_number.' is already Cancelled.');
    }

    public function test_the_board_separates_dine_in_from_takeaway(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);

        $dineIn = $this->placeOrder($store, $user, 'dine_in');
        $takeaway = $this->placeOrder($store, $user, 'takeaway');

        Livewire::actingAs($user)
            ->test(OrderQueue::class)
            ->call('setTypeFilter', 'dine_in')
            ->assertSet('typeFilter', 'dine_in')
            ->assertSee($dineIn->order_number)
            ->assertDontSee($takeaway->order_number);
    }

    public function test_the_order_queue_route_is_staff_only(): void
    {
        $this->get(route('pos.orders'))->assertRedirect('/login');

        $store = Store::factory()->create();
        $cashier = $this->staff($store);

        $this->actingAs($cashier)->get(route('pos.orders'))->assertOk();
    }

    public function test_api_can_list_advance_and_cancel_bills(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $order = $this->placeOrder($store, $user);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders?status=open')
            ->assertOk()
            ->assertJsonCount(1, 'orders');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('order.status', 'preparing');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('order.status', 'cancelled');
    }

    public function test_api_cannot_touch_another_stores_bill(): void
    {
        $store = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $user = $this->staff($store);
        $intruder = $this->staff($otherStore);
        $order = $this->placeOrder($store, $user);

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertNotFound();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }
}
