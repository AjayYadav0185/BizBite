<?php

namespace App\Services;

use App\Models\Enums\OrderStatus;
use App\Models\Enums\PaymentMode;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Place an order through a single code path shared by the web POS
 * (Phase 1) and the Flutter API (Phase 2).
 *
 * The service never trusts client side totals: it re-reads every food item
 * price from the database and re-calculates the order total server side.
 * All business queries, including the food item lookups, are automatically
 * constrained to the authenticated store by the global tenant scopes.
 */
class CheckoutService
{
    /**
     * Place an order.
     *
     * @param  array  $payload  The normalized cart payload:
     *                           [
     *                             'items' => [
     *                               ['food_item_id' => 1, 'quantity' => 2],
     *                             ],
     *                             'payment_mode' => 'cash'|'upi'|'card',
     *                           ]
     * @param  \App\Models\User  $user  The authenticated cashier / admin.
     * @return array<string, mixed>  An array describing the created order.
     */
    public static function place(array $payload, User $user)
    {
        return DB::transaction(function () {
            $paymentMode = PaymentMode::from(
                (string) ($payload['payment_mode'] ?? 'cash')
            );
            $items = new Collection($payload['items'] ?? []);

            if ($items->isEmpty()) {
                throw new InvalidArgumentException(
                    'Order must contain at least one item.'
                );
            }

            // Re-read menu prices from the database. Because FoodItem is scoped
            // by StoreScope, a client can never inject item ids from another
            // store into the order.
            $foodItemIds = $items->pluck('food_item_id');
            $foodItems = FoodItem::whereKey($foodItemIds)->
                get()->keyBy('id');

            if (count($foodItems) !== count($foodItemIds->unique())) {
                throw new InvalidArgumentException(
                    'One or more food items could not be found on this store\'s menu.'
                );
            }

            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $foodItem = $foodItems[$item['food_item_id']];
                $quantity = max((int) ($item['quantity'] ?? 1), 1);
                $subtotal = round($foodItem->price * $quantity, 2);

                $totalAmount = round($totalAmount + $subtotal, 2);

                $orderItems[] = [
                    'food_item_name' => $foodItem->name,
                    'quantity' => $quantity,
                    'price' => $foodItem->price,
                    'subtotal' => $subtotal,
                ];
            }

            $order = Order::create([
                'store_id' => Context::get('current_store_id'),
                'user_id' => $user->id,
                'order_number' => static::generateOrderNumber($user->store_id),
                'total_amount' => $totalAmount,
                'payment_mode' => $paymentMode,
                'status' => OrderStatus::Completed,
            ]);

            $order->items()->createMany($orderItems);

            return [
                'order' => $order,
                'items' => $orderItems,
                'total_amount' => $totalAmount,
            ];
        });
    }

    /**
     * Generate a human friendly, per store order number.
     *
     * @param  int|string  $storeId
     * @return string
     */
    protected static function generateOrderNumber($storeId)
    {
        $today = date('Ymd');
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $count = Order::whereBetween('created_at', [$start, $end])->count();

        return sprintf(
            '%s-%s-%s',
            Str::padLeft((string) $storeId, 3, '0'),
            $today,
            Str::padLeft((string) ($count + 1), 4, '0')
        );
    }
}