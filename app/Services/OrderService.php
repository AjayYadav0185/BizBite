<?php

namespace App\Services;

use App\Models\Enums\OrderStatus;
use App\Models\Enums\PaymentMode;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Exceptions\OrderPlacementException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * BizBite's single checkout code path.
 *
 * Both the Staff POS Livewire component (Phase 1) and the Sanctum
 * OrderApiController consumed by the Flutter app (Phase 2) inject and call
 * this exact service, which guarantees that bill numbering, price
 * snapshotting and tenant isolation behave identically on every surface.
 *
 * Guarantees:
 *   - All persistence happens inside ONE database transaction; a failure
 *     rolls back the order AND all of its snapshot rows atomically.
 *   - Client supplied totals are NEVER trusted: every price is re-read from
 *     the `food_items` table and the grand total recomputed server side.
 *   - Cross-tenant attacks are structurally impossible: FoodItem carries the
 *     global StoreScope, so item ids from another store resolve to nothing.
 *   - Bill numbers are per-store, per-day sequences guarded by the
 *     `orders.store_id + order_number` unique index with retry-on-collision
 *     to stay safe under concurrent cashier/Flutter traffic.
 */
final class OrderService
{
    /**
     * Place (settle) an order.
     *
     * @param  \App\Models\User  $user  The authenticated cashier or admin.
     * @param  array<string, mixed>  $payload  Normalized cart payload:
     *   [
     *     'items'        => [['food_item_id' => 1, 'quantity' => 2], ...],
     *     'payment_mode' => 'cash'|'upi'|'card',
     *   ]
     * @return \App\Services\OrderReceipt  Immutable receipt for printing/JSON.
     *
     * @throws \App\Services\Exceptions\OrderPlacementException
     */
    public function place(User $user, array $payload): OrderReceipt
    {
        $paymentMode = PaymentMode::from(
            (string) ($payload['payment_mode'] ?? PaymentMode::Cash->value)
        );

        $lineItems = $this->normalizeLineItems($payload['items'] ?? []);

        if ($lineItems->isEmpty()) {
            throw OrderPlacementException::emptyCart();
        }

        try {
            // A transaction (plus retries on bill-number contention) keeps
            // order + snapshots atomic and safe for parallel cashiers.
            $receipt = DB::transaction(function () use ($user, $lineItems, $paymentMode): OrderReceipt {
                $store = $user->store()->lockForUpdate()->first();
                $storeId = (int) $store->id;

                // ---------------------------------------------------------
                // SERVER-SIDE PRICE SNAPSHOT
                // Re-read every item from the tenant-scoped menu. StoreScope
                // automatically constrains this lookup to the cashier's
                // store, so foreign item ids simply vanish from the result.
                // ---------------------------------------------------------
                $foodItems = FoodItem::query()
                    ->whereKey($lineItems->pluck('food_item_id')->unique())
                    ->get()
                    ->keyBy('id');

                $rejected = $lineItems
                    ->filter(fn (array $line): bool => ! isset($foodItems[$line['food_item_id']])
                        || ! $foodItems[$line['food_item_id']]->is_available)
                    ->map(fn (array $line): string => $foodItems[$line['food_item_id']]->name
                        ?? ('Menu item #'.$line['food_item_id']))
                    ->unique()
                    ->values()
                    ->all();

                if ($rejected !== []) {
                    throw OrderPlacementException::unavailableItems($rejected);
                }

                $totalAmount = '0';
                $snapshotRows = [];

                foreach ($lineItems as $line) {
                    /** @var \App\Models\FoodItem $foodItem */
                    $foodItem = $foodItems[$line['food_item_id']];

                    $quantity = $line['quantity'];
                    $unitPrice = (string) $foodItem->price;
                    $subtotal = bcmul($unitPrice, (string) $quantity, 2);
                    $totalAmount = bcadd($totalAmount, $subtotal, 2);

                    $snapshotRows[] = [
                        'food_item_name' => $foodItem->name,
                        'quantity' => $quantity,
                        'price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];
                }

                // ---------------------------------------------------------
                // COMMIT: order header + immutable snapshot rows.
                // ---------------------------------------------------------
                $order = Order::create([
                    'store_id' => $storeId,
                    'user_id' => $user->id,
                    'order_number' => $this->generateOrderNumber($storeId),
                    'total_amount' => $totalAmount,
                    'payment_mode' => $paymentMode,
                    'status' => OrderStatus::Completed,
                ]);

                $order->items()->createMany($snapshotRows);
                $order->setRelation('items', $order->items()->get());

                return OrderReceipt::fromOrder(
                    order: $order,
                    storeName: $store->name,
                    storePhone: $store->phone,
                    storeAddress: $store->address,
                    printHeader: $store->print_header,
                    printFooter: $store->print_footer,
                    cashierName: $user->name,
                );
            }, attempts: 3);
        } catch (OrderPlacementException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw new OrderPlacementException(
                'The bill could not be saved. Please retry checkout.',
                500,
                $exception
            );
        }

        return $receipt;
    }

    /**
     * Sanitize the client cart into well-formed line items.
     *
     * Merges duplicate food_item_id entries (a Flutter client and the POS
     * grid both guarantee uniqueness, but the API is a public boundary) and
     * coerces quantities into positive integers.
     *
     * @param  mixed  $items
     * @return \Illuminate\Support\Collection<int, array{food_item_id: int, quantity: int}>
     */
    private function normalizeLineItems(mixed $items): Collection
    {
        if (! is_array($items)) {
            return new Collection;
        }

        return (new Collection($items))
            ->filter(fn ($line): bool => is_array($line)
                && isset($line['food_item_id'])
                && is_numeric($line['food_item_id']))
            ->map(fn (array $line): array => [
                'food_item_id' => (int) $line['food_item_id'],
                'quantity' => max((int) ($line['quantity'] ?? 1), 1),
            ])
            ->groupBy('food_item_id')
            ->map(fn (Collection $lines, $foodItemId): array => [
                'food_item_id' => (int) $foodItemId,
                'quantity' => $lines->sum('quantity'),
            ])
            ->values();
    }

    /**
     * Human friendly, per-store, per-day bill number (e.g. 001-20260908-0042).
     *
     * Runs inside the caller's transaction with a row-level lock held on the
     * store, so two cashiers can never receive the same number; the unique
     * composite index `orders.store_id + order_number` is the final safety
     * net and the transaction's attempts:3 above retries on the rare race.
     */
    private function generateOrderNumber(int $storeId): string
    {
        $businessDay = now()->startOfDay();

        $billsToday = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereBetween('created_at', [$businessDay, now()->endOfDay()])
            ->lockForUpdate()
            ->count();

        return sprintf(
            '%s-%s-%s',
            Str::padLeft((string) $storeId, 3, '0'),
            $businessDay->format('Ymd'),
            Str::padLeft((string) ($billsToday + 1), 4, '0')
        );
    }
}