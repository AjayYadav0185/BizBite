<?php

/**
 * TEMPORARY smoke test — run with: php artisan tinker _smoke_order_service.php
 * (Safe to delete after validation.)
 */

use App\Models\Enums\PaymentMode;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderService;
use Illuminate\Support\Facades\Context;

$orders = app(OrderService::class);

// --- Act like the SetCurrentStore middleware for the cashier's request ------
$cashier = User::query()->where('email', 'cashier@bizbite.test')->firstOrFail();
Context::add('current_store_id', $cashier->store_id);
echo 'cashier store_id = '.$cashier->store_id."\n";

// A rival store + item that the cashier must NEVER be able to bill.
$rivalStore = Store::factory()->create(['name' => 'Rival Dosa Corner']);
$rivalItem = FoodItem::factory()->create([
    'store_id' => $rivalStore->id,
    'name' => 'Forbidden Item',
    'price' => 99.99,
]);

// ---------------------------------------------------------------------------
// 1. Happy path — transactional commit + price snapshotting
// ---------------------------------------------------------------------------
$menu = FoodItem::query()->where('store_id', $cashier->store_id)->orderBy('id')->take(2)->get();

$receipt = $orders->place($cashier, [
    'items' => [
        ['food_item_id' => $menu[0]->id, 'quantity' => 2],
        ['food_item_id' => $menu[1]->id, 'quantity' => 1],
    ],
    'payment_mode' => PaymentMode::Cash->value,
]);

$expected = (float) $menu[0]->price * 2 + (float) $menu[1]->price;
echo "1) HAPPY PATH: bill={$receipt->order->order_number} total={$receipt->totalAmount} (expected ".number_format($expected, 2).")\n";
echo '   receipt items='.count($receipt->items)." store={$receipt->storeName} header=\"{$receipt->printHeader}\"\n";

// ---------------------------------------------------------------------------
// 2. Cross-tenant attack — item from another store must be rejected
// ---------------------------------------------------------------------------
try {
    $orders->place($cashier, [
        'items' => [['food_item_id' => $rivalItem->id, 'quantity' => 1]],
        'payment_mode' => 'upi',
    ]);
    echo "2) FAIL: cross-tenant item was accepted!\n";
} catch (OrderPlacementException $e) {
    echo '2) CROSS-TENANT BLOCKED: '.$e->getMessage()."\n";
}

// ---------------------------------------------------------------------------
// 3. Empty cart rejection
// ---------------------------------------------------------------------------
try {
    $orders->place($cashier, ['items' => [], 'payment_mode' => 'cash']);
    echo "3) FAIL: empty cart accepted!\n";
} catch (OrderPlacementException $e) {
    echo '3) EMPTY CART BLOCKED: '.$e->getMessage()."\n";
}

// ---------------------------------------------------------------------------
// 4. Duplicate line items must merge quantities
// ---------------------------------------------------------------------------
$merged = $orders->place($cashier, [
    'items' => [
        ['food_item_id' => $menu[0]->id, 'quantity' => 1],
        ['food_item_id' => $menu[0]->id, 'quantity' => 4],
    ],
    'payment_mode' => PaymentMode::Upi->value,
]);
echo '4) MERGED QTY: '.((int) $merged->items[0]['quantity'] === 5 ? 'OK (5)' : 'FAIL ('.$merged->items[0]['quantity'].')')."\n";

// ---------------------------------------------------------------------------
// 5. Bill numbers are sequential per store per day
// ---------------------------------------------------------------------------
$seq = $orders->place($cashier, [
    'items' => [['food_item_id' => $menu[0]->id, 'quantity' => 1]],
    'payment_mode' => 'cash',
]);
echo '5) SEQUENCE: '.$receipt->order->order_number.' -> '.$merged->order->order_number.' -> '.$seq->order->order_number."\n";

// ---------------------------------------------------------------------------
// 6. Persistence integrity — snapshots written, totals match
// ---------------------------------------------------------------------------
$ordersCount = Order::query()->where('store_id', $cashier->store_id)->count();
$itemsCount = OrderItem::query()->count();
$totalsMatch = Order::query()->where('store_id', $cashier->store_id)->get()
    ->every(fn (Order $order) => (float) $order->total_amount === (float) $order->items->sum('subtotal'));
echo "6) INTEGRITY: orders={$ordersCount} snapshot_rows={$itemsCount} totals_match=".($totalsMatch ? 'YES' : 'NO')."\n";

echo "SMOKE TEST COMPLETE\n";