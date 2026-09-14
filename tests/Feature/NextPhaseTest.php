<?php

namespace Tests\Feature;

use App\Livewire\Pos\BillingDashboard;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\DiningTable;
use App\Models\Enums\UserRole;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Services\Exceptions\OrderPlacementException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NextPhaseTest extends TestCase
{
    use RefreshDatabase;

    private function staff(Store $store, UserRole $role = UserRole::Cashier): User
    {
        return User::factory()->create(['store_id' => $store->id, 'role' => $role]);
    }

    private function item(Store $store, float $price = 100.00): FoodItem
    {
        return FoodItem::factory()->create([
            'store_id' => $store->id, 'price' => $price, 'is_available' => true,
        ]);
    }

    private function place(Store $store, User $user, array $over = []): Order
    {
        $item = $this->item($store);

        return app(OrderService::class)->place($user, array_merge([
            'items' => [['food_item_id' => $item->id, 'quantity' => 1]],
            'payment_mode' => 'cash',
        ], $over))->order;
    }

    public function test_pos_can_settle_every_payment_mode_and_order_type(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $item = $this->item($store, 50.00);

        foreach (['cash', 'upi', 'card', 'credit'] as $mode) {
            Livewire::actingAs($user)->test(BillingDashboard::class)
                ->call('addItem', $item->id)
                ->call('setPaymentMode', $mode)
                ->assertSet('paymentMode', $mode)
                ->call('checkout', $mode)
                ->assertDispatched('trigger-print')
                ->assertHasNoErrors();
        }

        Livewire::actingAs($user)->test(BillingDashboard::class)
            ->call('setOrderType', 'dine_in')->assertSet('orderType', 'dine_in')
            ->set('tableNumberInput', 'T7')
            ->call('addItem', $item->id)
            ->call('checkout', 'cash')
            ->assertDispatched('trigger-print');

        $this->assertSame(5, Order::query()->count());
        $this->assertDatabaseHas('tbl_pos_orders', ['table_number' => 'T7', 'order_type' => 'dine_in']);
    }

    public function test_split_tender_persists_legs(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $item = $this->item($store, 100.00);
        $order = app(OrderService::class)->place($user, [
            'items' => [['food_item_id' => $item->id, 'quantity' => 1]],
            'payment_mode' => 'split',
            'split_details' => [
                ['mode' => 'cash', 'amount' => '60.00'],
                ['mode' => 'upi', 'amount' => '40.00'],
            ],
            'tendered_amount' => '100.00',
            'upi_ref' => 'UTR123',
        ])->order;

        $this->assertSame('split', $order->fresh()->payment_mode->value);
        $this->assertCount(2, $order->payments()->get());
    }

    public function test_campaign_code_gives_discount(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        Campaign::create([
            'store_id' => $store->id, 'name' => 'Ten off', 'code' => 'TEN10',
            'type' => 'percent', 'value' => 10, 'is_active' => true,
        ]);
        $item = $this->item($store, 200.00);

        $order = app(OrderService::class)->place($user, [
            'items' => [['food_item_id' => $item->id, 'quantity' => 1]],
            'payment_mode' => 'cash', 'campaign_code' => 'ten10',
        ])->order->fresh();

        $this->assertSame('TEN10', $order->campaign_code);
        $this->assertTrue(bccomp((string) $order->campaign_discount, '0', 2) > 0);
    }

    public function test_partial_refund_guards(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $order = $this->place($store, $user);

        app(RefundService::class)->issue($user, $order, '30.00', 'spilled dal', 'cash');
        $this->assertSame('30.00', (string) $order->fresh()->refunded_amount);
        $this->assertDatabaseHas('tbl_pos_refunds', ['order_id' => $order->id]);
        $this->assertDatabaseHas('tbl_pos_audit_logs', [
            'action' => AuditLog::ACTION_ORDER_REFUND, 'entity_id' => $order->id,
        ]);

        try {
            app(RefundService::class)->issue($user, $order->fresh(), '9999.00', 'too much', 'cash');
            $this->fail('Expected over-refund to throw.');
        } catch (OrderPlacementException $e) {
            $this->assertStringContainsString('exceeds', $e->getMessage());
        }
    }

    public function test_delivery_refund_shift_reports_tables_staff(): void
    {
        $store = Store::factory()->create();
        $admin = $this->staff($store, UserRole::Admin);
        $user = $this->staff($store);

        $order = $this->place($store, $user, [
            'order_type' => 'delivery', 'delivery_address' => '12 MG Road', 'delivery_agent' => 'Ravi',
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/delivery", ['delivery_status' => 'assigned'])
            ->assertOk()->assertJsonPath('order.delivery_status', 'assigned');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/refund", ['amount' => 10, 'reason' => 'cold food'])
            ->assertOk();

        $other = Store::factory()->create();
        $intruder = $this->staff($other);
        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/orders/{$order->id}/refund", ['amount' => 5, 'reason' => 'x'])
            ->assertNotFound();

        $svc = app(\App\Services\ShiftService::class);
        $shift = $svc->open($user, '500.00', 'morning');
        $closed = $svc->close($user, $shift, '700.00');
        $this->assertSame('closed', $closed->status);
        $this->actingAs($user)->get(route('pos.shift'))->assertOk();

        $today = now()->toDateString();
        $hourly = app(\App\Services\ReportService::class)->hourly($today);
        $this->assertCount(24, $hourly);
        $this->assertSame(1, array_sum(array_column($hourly, 'bills')));
        $this->actingAs($user, 'sanctum')->getJson('/api/reports/hourly')->assertOk();
        $this->actingAs($user, 'sanctum')->getJson('/api/reports/range')->assertOk();

        DiningTable::create(['store_id' => $store->id, 'table_number' => 'T3', 'seats' => 4]);
        $dine = $this->place($store, $user, ['order_type' => 'dine_in', 'table_number' => 'T3']);
        $this->assertSame('occupied', DiningTable::query()->where('table_number', 'T3')->first()->status);
        app(OrderService::class)->updateStatus($user, $dine, \App\Models\Enums\OrderStatus::Completed);
        $this->assertSame('available', DiningTable::query()->where('table_number', 'T3')->first()->status);

        $this->actingAs($user, 'sanctum')->postJson('/api/campaigns', ['name' => 'X'])->assertForbidden();
        $this->actingAs($admin, 'sanctum')->postJson('/api/campaigns', [
            'name' => 'Festive', 'code' => 'FEST10', 'type' => 'percent', 'value' => 10,
        ])->assertCreated();
        $this->actingAs($admin, 'sanctum')->postJson('/api/tables', ['table_number' => 'T9'])->assertCreated();
        $this->actingAs($admin, 'sanctum')->postJson('/api/staff', [
            'name' => 'New Cashier', 'email' => 'newcashier@example.com', 'password' => 'secret123',
        ])->assertCreated();
    }
}
