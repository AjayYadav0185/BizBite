<?php

namespace Tests\Feature;

use App\Livewire\Admin\MenuManager;
use App\Livewire\Pos\BillingDashboard;
use App\Models\AuditLog;
use App\Models\Category;
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
 * Stock control — priority feature §5 (plan §4.6).
 *
 * Business rules verified end to end:
 *   1. A tracked item's stock drops by the sold quantity at checkout.
 *   2. A NULL stock_quantity means "untracked" and is never counted.
 *   3. Out-of-stock items cannot be added to the cart (POS guard).
 *   4. The cart can never exceed the tracked shelf count (POS guard).
 *   5. OrderService re-validates stock server side, so a race that drains the
 *      shelf between tapping and settling still cannot oversell.
 *   6. The owner sees a low-stock alert and can set counts from MenuManager.
 */
class StockControlTest extends TestCase
{
    use RefreshDatabase;

    private function staff(Store $store, UserRole $role = UserRole::Cashier): User
    {
        return User::factory()->create([
            'store_id' => $store->id,
            'role' => $role,
        ]);
    }

    public function test_settling_a_bill_decrements_tracked_stock(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'price' => 40.00,
            'is_available' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
        ]);

        Livewire::actingAs($user)
            ->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->call('incrementQuantity', $item->id)
            ->call('checkout', 'cash')
            ->assertHasNoErrors();

        $this->assertSame(8, $item->fresh()->stock_quantity);
    }

    public function test_untracked_items_are_never_counted(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'price' => 30.00,
            'is_available' => true,
            'stock_quantity' => null,
        ]);

        $this->assertFalse($item->tracksStock());

        Livewire::actingAs($user)
            ->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->call('checkout', 'cash')
            ->assertHasNoErrors();

        $this->assertNull($item->fresh()->stock_quantity);
    }

    public function test_out_of_stock_items_cannot_be_added_to_the_cart(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'is_available' => true,
            'stock_quantity' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(BillingDashboard::class)
            ->call('addItem', $item->id)
            ->assertSet('cart', [])
            ->assertSet('error', $item->name.' is out of stock.');
    }

    public function test_the_cart_is_capped_at_the_available_stock(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'is_available' => true,
            'stock_quantity' => 2,
        ]);

        $component = Livewire::actingAs($user)->test(BillingDashboard::class);

        $component->call('addItem', $item->id)
            ->call('incrementQuantity', $item->id)
            ->assertSet('cart.'.$item->id.'.quantity', 2)
            ->call('incrementQuantity', $item->id)   // 3rd tap must be refused
            ->assertSet('cart.'.$item->id.'.quantity', 2)
            ->assertSet('error', 'Only 2 left for '.$item->name.'.');
    }

    public function test_order_service_blocks_overselling_at_the_server(): void
    {
        $store = Store::factory()->create();
        $user = $this->staff($store);
        $item = FoodItem::factory()->create([
            'store_id' => $store->id,
            'price' => 50.00,
            'is_available' => true,
            'stock_quantity' => 1,
        ]);

        try {
            app(OrderService::class)->place($user, [
                'items' => [['food_item_id' => $item->id, 'quantity' => 5]],
                'payment_mode' => 'cash',
            ]);

            $this->fail('Expected the oversell to be rejected.');
        } catch (OrderPlacementException $exception) {
            $this->assertStringContainsString($item->name, $exception->getMessage());
        }

        $this->assertSame(1, $item->fresh()->stock_quantity);
        $this->assertSame(0, Order::query()->count());
    }

    public function test_low_stock_flags_and_the_menu_alert_follow_the_threshold(): void
    {
        $store = Store::factory()->create();
        $admin = $this->staff($store, UserRole::Admin);
        $category = Category::factory()->create(['store_id' => $store->id]);

        $low = FoodItem::factory()->create([
            'store_id' => $store->id, 'category_id' => $category->id,
            'name' => 'Samosa', 'stock_quantity' => 2, 'low_stock_threshold' => 5,
        ]);
        $out = FoodItem::factory()->create([
            'store_id' => $store->id, 'category_id' => $category->id,
            'name' => 'Kachori', 'stock_quantity' => 0, 'low_stock_threshold' => 5,
        ]);
        $healthy = FoodItem::factory()->create([
            'store_id' => $store->id, 'category_id' => $category->id,
            'name' => 'Chai', 'stock_quantity' => 100, 'low_stock_threshold' => 5,
        ]);

        $this->assertTrue($low->isLowStock());
        $this->assertTrue($out->isOutOfStock());
        $this->assertFalse($healthy->isLowStock());
        $this->assertFalse($healthy->isOutOfStock());

        // The owner-facing alert renders the flagged items.
        Livewire::actingAs($admin)
            ->test(MenuManager::class)
            ->assertSee('Low Stock Alert')
            ->assertSee('Samosa')
            ->assertSee('Kachori')
            ->assertSee('sold out');
    }

    public function test_admin_can_set_stock_when_creating_and_editing_an_item(): void
    {
        $store = Store::factory()->create();
        $admin = $this->staff($store, UserRole::Admin);
        $category = Category::factory()->create(['store_id' => $store->id]);

        $component = Livewire::actingAs($admin)->test(MenuManager::class);

        $component->set('newItem.category_id', $category->id)
            ->set('newItem.name', 'Paneer Roll')
            ->set('newItem.price', '120')
            ->set('newItem.stock_quantity', '25')
            ->set('newItem.low_stock_threshold', '4')
            ->call('createItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tbl_pos_food_items', [
            'store_id' => $store->id,
            'name' => 'Paneer Roll',
            'stock_quantity' => 25,
            'low_stock_threshold' => 4,
        ]);

        $item = FoodItem::query()->where('name', 'Paneer Roll')->firstOrFail();

        // Restocking is audited for the owner (stock shrinkage is a trust event).
        $component->call('editItem', $item->id)
            ->set('editingItem.stock_quantity', '60')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertSame(60, $item->fresh()->stock_quantity);
        $this->assertDatabaseHas('tbl_pos_audit_logs', [
            'store_id' => $store->id,
            'action' => AuditLog::ACTION_STOCK_UPDATED,
            'entity_id' => $item->id,
        ]);
    }
}
