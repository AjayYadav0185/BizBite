<?php

namespace App\Services;

use App\Models\Enums\OrderType;
use App\Models\Enums\PaymentMode;
use App\Models\Enums\PaymentStatus;
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
     *                                                                                                            Price-snapshot rows as they were written to
     *                                                                                                            `order_items` (server-computed, never client
     *                                                                                                            supplied).
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
        public readonly string $subtotal = '0.00',
        public readonly string $discountAmount = '0.00',
        public readonly string $taxAmount = '0.00',
        public readonly string $roundOff = '0.00',
        public readonly ?OrderType $orderType = null,
        public readonly ?PaymentStatus $paymentStatus = null,
        public readonly ?string $upiRef = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerPhone = null,
        public readonly string $walletDeduction = '0.00',
        public readonly ?string $walletBalanceAfter = null,
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
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->taxAmount,
            'round_off' => $this->roundOff,
            'total_amount' => $this->totalAmount,
            'payment_mode' => $this->paymentMode->value,
            'payment_status' => $this->paymentStatus?->value ?? $this->order->payment_status?->value,
            'status' => $this->order->status->value,
            'order_type' => $this->orderType?->value ?? $this->order->order_type?->value,
            'upi_ref' => $this->upiRef ?? $this->order->upi_ref,
            'customer_name' => $this->customerName ?? $this->order->customer_name,
            'customer_phone' => $this->customerPhone ?? $this->order->customer_phone,
            // Customer Wallet: 1% points movement for this bill.
            'wallet_deduction' => $this->walletDeduction,
            'wallet_balance_after' => $this->walletBalanceAfter,
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
            'currency' => 'INR',
            'items' => array_map(
                fn (array $item): array => [
                    'food_item_name' => $item['food_item_name'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (string) $item['price'],
                    'subtotal' => (string) $item['subtotal'],
                    'gst_rate' => (int) ($item['gst_rate'] ?? 0),
                    'gst_amount' => (string) ($item['gst_amount'] ?? '0.00'),
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
        string $walletDeduction = '0.00',
        ?string $walletBalanceAfter = null,
    ): self {
        return new self(
            order: $order,
            items: $order->items
                ->map(fn (OrderItem $item): array => [
                    'food_item_name' => $item->food_item_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                    'gst_rate' => $item->gst_rate ?? 0,
                    'gst_amount' => $item->gst_amount ?? '0.00',
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
            subtotal: (string) ($order->subtotal ?? $order->total_amount),
            discountAmount: (string) ($order->discount_amount ?? '0.00'),
            taxAmount: (string) ($order->tax_amount ?? '0.00'),
            roundOff: (string) ($order->round_off ?? '0.00'),
            orderType: $order->order_type,
            paymentStatus: $order->payment_status,
            upiRef: $order->upi_ref,
            customerName: $order->customer_name,
            customerPhone: $order->customer_phone,
            walletDeduction: $walletDeduction,
            walletBalanceAfter: $walletBalanceAfter,
        );
    }
}
