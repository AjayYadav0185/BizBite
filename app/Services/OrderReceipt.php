<?php

namespace App\Services;

use App\Models\Enums\PaymentMode;
use App\Models\Order;
use App\Models\OrderItem;

/**
 * Immutable receipt value object returned by {@see OrderService::place()}.
 *
 * It intentionally contains EVERYTHING a thermal receipt needs (store
 * branding, snapshot rows, totals) so that:
 *
 *   - the Staff POS Livewire component can bind it straight into the hidden
 *     `@media print` DOM block and fire the browser print dialog, and
 *   - the Sanctum API controller can serialize it 1:1 into the JSON response
 *     consumed by the Phase 2 Flutter app (which renders its own ESC/POS
 *     printout).
 *
 * No persistence logic lives here — this is a pure data carrier.
 */
final class OrderReceipt
{
    /**
     * @param  Order  $order  The persisted order model (with order_number,
     *                        total_amount, payment_mode, status resolved).
     * @param  array<int, array{food_item_name: string, quantity: int, price: string, subtotal: string}>  $items
     *                        Price-snapshot rows as they were written to
     *                        `order_items` (server-computed, never client
     *                        supplied).
     * @param  string  $totalAmount  Grand total, 2 decimal places.
     * @param  string  $storeName  Tenant display name for the receipt header.
     * @param  string|null  $storePhone  Tenant contact phone.
     * @param  string|null  $storeAddress  Tenant address line.
     * @param  string|null  $printHeader  Owner-customized receipt header text.
     * @param  string|null  $printFooter  Owner-customized receipt footer text.
     * @param  PaymentMode  $paymentMode  Settled payment mode.
     * @param  string  $placedAt  Human readable billing timestamp.
     * @param  string|null  $cashierName  Staff member who settled the bill.
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $items,
        public readonly string $totalAmount,
        public readonly string $storeName,
        public readonly ?string $storePhone,
        public readonly ?string $storeAddress,
        public readonly ?string $printHeader,
        public readonly ?string $printFooter,
        public readonly PaymentMode $paymentMode,
        public readonly string $placedAt,
        public readonly ?string $cashierName = null,
    ) {}

    /**
     * Total quantity of items on the bill (for the "x ITEMS" summary line).
     */
    public function totalQuantity(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }

    /**
     * Serialize for the Livewire receipt block AND the Flutter JSON payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->totalAmount,
            'payment_mode' => $this->paymentMode->value,
            'status' => $this->order->status->value,
            'store' => [
                'name' => $this->storeName,
                'phone' => $this->storePhone,
                'address' => $this->storeAddress,
                'print_header' => $this->printHeader,
                'print_footer' => $this->printFooter,
            ],
            'cashier' => $this->cashierName,
            'placed_at' => $this->placedAt,
            'total_quantity' => $this->totalQuantity(),
            'items' => array_map(
                fn (array $item): array => [
                    'food_item_name' => $item['food_item_name'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (string) $item['price'],
                    'subtotal' => (string) $item['subtotal'],
                ],
                $this->items
            ),
        ];
    }

    /**
     * Build the DTO from a freshly persisted order graph.
     *
     * Kept as a named constructor so OrderService stays readable and so the
     * receipt shape is decided in exactly one place.
     */
    public static function fromOrder(
        Order $order,
        string $storeName,
        ?string $storePhone = null,
        ?string $storeAddress = null,
        ?string $printHeader = null,
        ?string $printFooter = null,
        ?string $cashierName = null,
    ): self {
        return new self(
            order: $order,
            items: $order->items
                ->map(fn (OrderItem $item): array => [
                    'food_item_name' => $item->food_item_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ])
                ->all(),
            totalAmount: (string) $order->total_amount,
            storeName: $storeName,
            storePhone: $storePhone,
            storeAddress: $storeAddress,
            printHeader: $printHeader,
            printFooter: $printFooter,
            paymentMode: $order->payment_mode,
            placedAt: $order->created_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A'),
            cashierName: $cashierName,
        );
    }
}