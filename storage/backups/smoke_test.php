<?php

// Throwaway end-to-end write test for the renamed tbl_pos_* schema.
// Writes an order + line inside a transaction, then ALWAYS rolls back.

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\FoodItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

printf(
    "users=%d items=%d orders=%d sessions_tbl=%s\n",
    User::count(),
    FoodItem::count(),
    Order::count(),
    DB::select('select count(*) as c from tbl_sessions')[0]->c
);

DB::beginTransaction();
try {
    $order = Order::create([
        'store_id' => 1,
        'order_number' => 'SMOKE-TEST-001',
        'subtotal' => 120,
        'discount_amount' => 0,
        'tax_amount' => 6,
        'round_off' => 0,
        'total_amount' => 126,
        'payment_mode' => 'cash',
        'payment_status' => 'paid',
        'status' => 'completed',
        'order_type' => 'takeaway',
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'food_item_id' => FoodItem::first()->id,
        'food_item_name' => 'smoke test',
        'quantity' => 1,
        'price' => 120,
        'subtotal' => 120,
        'discount_amount' => 0,
        'gst_rate' => 5,
        'gst_amount' => 6,
    ]);
    printf(
        "WRITE_OK order_id=%d lines=%d\n",
        $order->id,
        $order->items()->count()
    );
} finally {
    DB::rollBack();
    printf("ROLLED_BACK orders=%d\n", Order::count());
}
