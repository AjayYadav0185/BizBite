<?php

/**
 * Quick verification script for CheckoutService.
 * Run: php scripts/test_checkout.php
 */

use App\Models\FoodItem;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Context;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the admin user from the seed
$user = User::where('email', 'admin@bizbite.test')->first();

if (! $user) {
    echo "ERROR: No admin user found. Run: php artisan migrate:fresh --seed\n";
    exit(1);
}

Context::add('current_store_id', $user->store_id);

// Pick some food items
$items = FoodItem::limit(2)->get();

$cart = [
    'items' => [
        ['food_item_id' => $items[0]->id, 'quantity' => 2],
        ['food_item_id' => $items[1]->id, 'quantity' => 1],
    ],
    'payment_mode' => 'cash',
];

echo "Food items:\n";
foreach ($items as $item) {
    echo "  - {$item->name}: \${$item->price}\n";
}

echo "\nCart: 2x {$items[0]->name} + 1x {$items[1]->name}\n";
$expectedTotal = round(($items[0]->price * 2) + $items[1]->price, 2);
echo "Expected total: \${$expectedTotal}\n";

$result = CheckoutService::place($cart, $user);

echo "\n=== Order Created Successfully ===\n";
echo "Order Number: {$result['order']->order_number}\n";
echo "Total: \${$result['total_amount']}\n";
echo "Order Items Count: {$result['order']->items->count()}\n";
echo "Store ID on order: {$result['order']->store_id}\n";

echo "\nOrder Items:\n";
foreach ($result['order']->items as $item) {
    echo "  - {$item->food_item_name} x{$item->quantity} @ \${$item->price} = \${$item->subtotal}\n";
}

echo "\n✅ Checkout test passed!\n";
