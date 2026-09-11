<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Enums\OrderStatus;
use App\Models\Enums\OrderType;
use App\Models\Enums\PaymentMode;
use App\Models\Enums\PaymentStatus;
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
 * this exact service, which guarantees that bill numbering, GST math,
 * price snapshotting and tenant isolation behave identically on every surface.
 *
 * Guarantees:
 *   - All persistence happens inside ONE database transaction; a failure
 *     rolls back the order AND all of its snapshot rows atomically.
 *   - Client supplied totals are NEVER trusted: every price is re-read from
 *     the `tbl_pos_food_items` table and the grand total recomputed server side.
 *   - Cross-tenant attacks are structurally impossible: FoodItem carries the
 *     global StoreScope, so item ids from another store resolve to nothing.
 *   - Bill numbers are per-store, per-day sequences guarded by the
 *     `tbl_pos_orders.store_id + order_number` unique index with retry-on-collision
 *     to stay safe under concurrent cashier/Flutter traffic.
 *   - Flutter retries are idempotent: pass `idempotency_key` and a replay
 *     returns the original receipt instead of creating a duplicate bill.
 *   - Wallet side-effect: 1% of the settled total is debited from the
 *     cashier's wallet (capped at the available balance) inside the SAME
 *     transaction, with a `Bill deduction` ledger row.
 */
final class OrderService
{
    public function __construct(private readonly WalletService $wallet) {}
    /**
     * Place (settle) an order.
     *
     * @param  User  $user  The authenticated cashier or admin.
     * @param  array<string, mixed>  $payload  Normalized cart payload:
     *                                         [
     *                                         'items'           => [['food_item_id' => 1, 'quantity' => 2], ...],
     *                                         'payment_mode'    => 'cash'|'upi'|'card'|'credit'|'split',
     *                                         'order_type'      => 'dine_in'|'takeaway'|'parcel'|'delivery',
     *                                         'discount_amount' => '20.00',
     *                                         'customer_name'   => '...', 'customer_phone' => '98...',
     *                                         'upi_ref'         => 'UPI txn / UTR',
     *                                         'idempotency_key' => 'client uuid (Flutter offline retry)',
     *                                         ]
     * @return OrderReceipt Immutable receipt for printing/JSON.
     *
     * @throws OrderPlacementException
     */
    public function place(User $user, array $payload): OrderReceipt
    {
        $paymentMode = PaymentMode::tryFrom(
            (string) ($payload['payment_mode'] ?? PaymentMode::Cash->value)
        ) ?? PaymentMode::Cash;

        $orderType = OrderType::tryFrom(
            (string) ($payload['order_type'] ?? OrderType::Takeaway->value)
        ) ?? OrderType::Takeaway;

        $lineItems = $this->normalizeLineItems($payload['items'] ?? []);

        if ($lineItems->isEmpty()) {
            throw OrderPlacementException::emptyCart();
        }

        try {
            // A transaction (plus retries on bill-number contention) keeps
            // order + snapshots atomic and safe for parallel cashiers.
            $receipt = DB::transaction(function () use ($user, $lineItems, $paymentMode, $orderType, $payload): OrderReceipt {
                $store = $user->store()->lockForUpdate()->first();
                $storeId = (int) $store->id;

                // Idempotency for Flutter offline retries: a replayed key returns
                // the original receipt instead of minting a duplicate bill.
                $idempotencyKey = isset($payload['idempotency_key']) && is_string($payload['idempotency_key']) && trim($payload['idempotency_key']) !== ''
                    ? substr(trim($payload['idempotency_key']), 0, 64)
                    : null;

                if ($idempotencyKey !== null) {
                    $existing = Order::query()->withoutGlobalScopes()
                        ->where('store_id', $storeId)
                        ->where('idempotency_key', $idempotencyKey)
                        ->with('items')
                        ->first();

                    if ($existing !== null) {
                        return OrderReceipt::fromOrder(
                            order: $existing,
                            storeName: $store->name,
                            storePhone: $store->phone,
                            storeAddress: $store->address,
                            printHeader: $store->print_header,
                            printFooter: $store->print_footer,
                            cashierName: $user->name,
                        );
                    }
                }

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

                $subtotalAmount = '0';
                $taxAmount = '0';
                $snapshotRows = [];

                foreach ($lineItems as $line) {
                    /** @var FoodItem $foodItem */
                    $foodItem = $foodItems[$line['food_item_id']];

                    $quantity = $line['quantity'];
                    $unitPrice = (string) $foodItem->price;
                    $lineSubtotal = bcmul($unitPrice, (string) $quantity, 2);
                    $subtotalAmount = bcadd($subtotalAmount, $lineSubtotal, 2);

                    // GST per line (inclusive-price math kept simple for dhabas/QSRs):
                    // gst = line_total * rate / (100 + rate), rounded to 2dp.
                    $gstRate = (int) ($foodItem->gst_rate ?? 5);
                    $lineGst = $gstRate > 0
                        ? bcdiv(bcmul($lineSubtotal, (string) $gstRate, 4), (string) (100 + $gstRate), 2)
                        : '0.00';
                    $taxAmount = bcadd($taxAmount, $lineGst, 2);

                    $snapshotRows[] = [
                        'food_item_id' => $foodItem->id,
                        'food_item_name' => $foodItem->name,
                        'quantity' => $quantity,
                        'price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                        'discount_amount' => '0.00',
                        'gst_rate' => $gstRate,
                        'gst_amount' => $lineGst,
                    ];
                }

                // Flat bill-level discount (never below zero), then round-off to the rupee.
                $discountAmount = '0.00';
                if (isset($payload['discount_amount']) && is_numeric($payload['discount_amount'])) {
                    $discountAmount = number_format(max((float) $payload['discount_amount'], 0), 2, '.', '');
                    if (bccomp($discountAmount, $subtotalAmount, 2) > 0) {
                        $discountAmount = $subtotalAmount;
                    }
                }

                $afterDiscount = bcsub($subtotalAmount, $discountAmount, 2);
                // Tax stays proportional after discount.
                if (bccomp($subtotalAmount, '0', 2) > 0 && bccomp($discountAmount, '0', 2) > 0) {
                    $taxAmount = bcdiv(bcmul($taxAmount, $afterDiscount, 4), $subtotalAmount, 2);
                }
                $grandTotal = bcadd($afterDiscount, '0', 2);
                // Indian cash rounding: nearest rupee (UPI keeps paise — see receipt).
                $rounded = (string) round((float) $grandTotal);
                $roundOff = bcsub($rounded, $grandTotal, 2);

                $customerName = isset($payload['customer_name']) && is_string($payload['customer_name'])
                    ? substr(trim($payload['customer_name']), 0, 80) ?: null
                    : null;
                $customerPhone = isset($payload['customer_phone']) && is_string($payload['customer_phone'])
                    ? preg_replace('/[^\d+]/', '', substr(trim($payload['customer_phone']), 0, 15)) ?: null
                    : null;
                $upiRef = isset($payload['upi_ref']) && is_string($payload['upi_ref'])
                    ? substr(trim($payload['upi_ref']), 0, 60) ?: null
                    : null;

                // ---------------------------------------------------------
                // COMMIT: order header + immutable snapshot rows + payment leg.
                // ---------------------------------------------------------
                $order = Order::create([
                    'store_id' => $storeId,
                    'user_id' => $user->id,
                    'order_number' => $this->generateOrderNumber($storeId),
                    'subtotal' => $subtotalAmount,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'round_off' => $roundOff,
                    'total_amount' => $rounded,
                    'payment_mode' => $paymentMode,
                    'payment_status' => PaymentStatus::Paid,
                    'status' => OrderStatus::Completed,
                    'order_type' => $orderType,
                    'upi_ref' => $paymentMode === PaymentMode::Upi ? $upiRef : null,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'idempotency_key' => $idempotencyKey,
                ]);

                $order->items()->createMany($snapshotRows);
                $order->payments()->create([
                    'store_id' => $storeId,
                    'mode' => $paymentMode->value,
                    'amount' => $rounded,
                    'status' => 'success',
                    'upi_ref' => $paymentMode === PaymentMode::Upi ? $upiRef : null,
                    'paid_at' => now(),
                ]);
                $order->setRelation('items', $order->items()->get());

                // Customer Wallet: 1% of the settled total is debited from
                // the cashier's wallet (capped so it never goes negative).
                // Runs inside this same transaction — bill + wallet are atomic.
                // Idempotent replays return early above, so a retry never
                // double-debits.
                $walletDeduction = $this->wallet->deductForBill($user, (string) $rounded, $order->id);
                $walletBalanceAfter = number_format((float) ($user->fresh()->wallet_balance ?? 0), 2, '.', '');

                // Owner audit trail: discounts, credit bills and UPI refs are
                // the entries the admin reviews (cashier writes, admin reads).
                if (bccomp($discountAmount, '0', 2) > 0) {
                    Audit::record(
                        $user,
                        AuditLog::ACTION_ORDER_DISCOUNT,
                        'Discount ₹'.number_format((float) $discountAmount, 2).' given on bill '.$order->order_number.'.',
                        entityType: 'order',
                        entityId: $order->id,
                        entityName: $order->order_number,
                        new: ['discount_amount' => $discountAmount, 'total_amount' => $rounded],
                        amount: $discountAmount,
                    );
                }

                if ($paymentMode === PaymentMode::Credit) {
                    Audit::record(
                        $user,
                        AuditLog::ACTION_CREDIT_BILL,
                        'Credit bill '.$order->order_number.' for ₹'.number_format((float) $rounded, 2).($customerName ? ' ('.$customerName.')' : '').'.',
                        entityType: 'order',
                        entityId: $order->id,
                        entityName: $order->order_number,
                        new: ['payment_mode' => 'credit', 'total_amount' => $rounded, 'customer_name' => $customerName, 'customer_phone' => $customerPhone],
                        amount: $rounded,
                    );
                }

                return OrderReceipt::fromOrder(
                    order: $order,
                    storeName: $store->name,
                    storePhone: $store->phone,
                    storeAddress: $store->address,
                    printHeader: $store->print_header,
                    printFooter: $store->print_footer,
                    cashierName: $user->name,
                    walletDeduction: $walletDeduction,
                    walletBalanceAfter: $walletBalanceAfter,
                );
            }, attempts: 3);
        } catch (OrderPlacementException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw new OrderPlacementException(
                'The bill could not be saved. Please retry checkout. ('.class_basename($exception).')',
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
     * @return Collection<int, array{food_item_id: int, quantity: int}>
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
     * composite index `tbl_pos_orders.store_id + order_number` is the final safety
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
