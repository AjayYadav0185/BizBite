<?php

// Throwaway end-to-end test of OrderService::place (the API checkout path).
// Runs inside a transaction and ALWAYS rolls back — no data is kept.

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$user = User::first();
$firstItem = DB::table('tbl_pos_food_items')->first();

DB::beginTransaction();
try {
    $service = app(App\Services\OrderService::class);
    $receipt = $service->place($user, [
        'payment_mode' => 'cash',
        'order_type' => 'takeaway',
        'items' => [
            ['food_item_id' => $firstItem->id, 'quantity' => 2],
        ],
        'idempotency_key' => 'smoke-test-' . uniqid(),
    ]);
    printf(
        "SERVICE_WRITE_OK total=%s items=%s\n",
        $receipt->total_amount ?? json_encode($receipt),
        count($receipt->items ?? [])
    );
} finally {
    DB::rollBack();
    printf(
        "ROLLED_BACK orders=%d payments=%d audit=%d\n",
        DB::table('tbl_pos_orders')->count(),
        DB::table('tbl_pos_payments')->count(),
        DB::table('tbl_pos_audit_logs')->count()
    );
}
