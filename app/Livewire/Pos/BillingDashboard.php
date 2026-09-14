<?php

namespace App\Livewire\Pos;

use App\Models\Category;
use App\Models\Enums\OrderType;
use App\Models\Enums\PaymentMode;
use App\Models\FoodItem;
use App\Models\User;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * STAFF POS PORTAL — high-speed billing screen.
 *
 * The entire cart lives in reactive public properties, so every tap/keystroke
 * is a Livewire round-trip with zero page reloads and zero client-side JS
 * state. Keyboard-first operation:
 *
 *   F2  -> clear the whole cart
 *   F4  -> focus the search box
 *   F8  -> CASH checkout
 *   F9  -> UPI checkout
 *
 * Checkout delegates persistence to the shared {@see OrderService} (the exact
 * same instance the Phase 2 Flutter API controller uses) and, on success,
 * updates the DOM with the receipt data before dispatching the
 * `trigger-print` browser event that fires the native thermal print dialog.
 */
#[Layout('layouts.app')]
#[Title('BizBite POS — Billing')]
final class BillingDashboard extends Component
{
    /** Currently selected category filter (null = ALL). */
    public ?int $activeCategoryId = null;

    /** Live menu search term. */
    public string $search = '';

    /**
     * THE CART. Keyed by food_item_id for O(1) lookups:
     *   [ 7 => ['id' => 7, 'name' => 'Vada Pav', 'price' => '12.00', 'quantity' => 3], ... ]
     *
     * @var array<int, array{id: int, name: string, price: string, quantity: int}>
     */
    public array $cart = [];

    /** Payment mode for the next settlement: cash|upi|card|credit|split. */
    public string $paymentMode = 'cash';

    /** Order type for the next bill (dine-in / takeaway / parcel / delivery). */
    public string $orderType = 'takeaway';

    /** Dine-in table number (Phase 3 table management). */
    public string $tableNumberInput = '';

    /** Campaign / loyalty code typed by the cashier (Phase 3). */
    public string $campaignInput = '';

    /** Cash tendered for tendered/change math (cash + split legs). */
    public string $tenderedInput = '';

    /** Split legs when paymentMode is split: [{mode, amount}]. */
    public array $splitLegs = [];

    /** UPI reference / UTR for UPI + split-with-UPI bills. */
    public string $upiRefInput = '';

    /** Delivery address + agent for delivery bills (Phase 3 workflow). */
    public string $deliveryAddressInput = '';

    public string $deliveryAgentInput = '';

    /** Receipt payload of the last settled bill (drives the print block). */
    public ?array $lastReceipt = null;

    /** Cashier-facing error banner. */
    public ?string $error = null;

    /** Cashier-facing success banner. */
    public ?string $success = null;

    /**
     * Bill-level discount in rupees, typed by the cashier. Kept as a string so
     * an empty input stays empty; {@see self::cartDiscount()} sanitizes and
     * clamps it against the subtotal on every render (MVP scope §6).
     */
    public string $discountInput = '';

    /** Free-text kitchen / customer note printed on the bill (max 200 chars). */
    public string $notesInput = '';

    /** Shared checkout service (also used by the Flutter API controller). */
    private OrderService $orders;

    /**
     * Livewire 3 ONLY calls mount() on the very first full-page render.
     * Every subsequent interaction (wire:click, live search, checkout) is a
     * re-hydration that skips mount() and runs boot() instead. The OrderService
     * must therefore be re-bound in boot() so checkout() never touches an
     * uninitialized property after the first menu tap — this was the cause of
     * "Typed property BillingDashboard::$orders must not be accessed before
     * initialization" whenever a cashier tapped items then pressed settle.
     */
    public function boot(OrderService $orders): void
    {
        $this->orders = $orders;
    }

    public function mount(): void
    {
        $this->authorize('access-pos-portal');
    }

    // ---------------------------------------------------------------------
    // Menu data (tenant scoped automatically via StoreScope)
    // ---------------------------------------------------------------------

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function menuItems(): Collection
    {
        return FoodItem::query()
            ->with('category:id,name')
            ->where('is_available', true)
            ->when($this->activeCategoryId, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(trim($this->search) !== '', fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%'))
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    // ---------------------------------------------------------------------
    // Reactive cart management
    // ---------------------------------------------------------------------

    public function addItem(int $foodItemId): void
    {
        /** @var FoodItem|null $item */
        $item = FoodItem::query()->where('is_available', true)->find($foodItemId);

        if ($item === null) {
            $this->error = 'That item is no longer available.';

            return;
        }

        // Stock control (priority feature §5): never let the cart exceed the
        // tracked shelf count. OrderService re-checks this at checkout, so the
        // guarantee holds even if another cashier drains the stock meanwhile.
        if ($item->isOutOfStock()) {
            $this->error = $item->name.' is out of stock.';

            return;
        }

        $inCart = $this->cart[$foodItemId]['quantity'] ?? 0;

        if (! $item->canFulfil($inCart + 1)) {
            $this->error = 'Only '.max((int) $item->stock_quantity, 0).' left for '.$item->name.'.';

            return;
        }

        if (isset($this->cart[$foodItemId])) {
            $this->cart[$foodItemId]['quantity']++;
        } else {
            $this->cart[$foodItemId] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (string) $item->price,
                'quantity' => 1,
            ];
        }

        $this->error = null;
    }

    public function incrementQuantity(int $foodItemId): void
    {
        if (! isset($this->cart[$foodItemId])) {
            return;
        }

        $item = FoodItem::query()->find($foodItemId);

        if ($item !== null && ! $item->canFulfil($this->cart[$foodItemId]['quantity'] + 1)) {
            $this->error = $item->tracksStock()
                ? 'Only '.max((int) $item->stock_quantity, 0).' left for '.$item->name.'.'
                : $item->name.' is no longer available.';

            return;
        }

        $this->cart[$foodItemId]['quantity']++;
        $this->error = null;
    }

    public function decrementQuantity(int $foodItemId): void
    {
        if (! isset($this->cart[$foodItemId])) {
            return;
        }

        if (--$this->cart[$foodItemId]['quantity'] < 1) {
            unset($this->cart[$foodItemId]);
        }
    }

    public function removeItem(int $foodItemId): void
    {
        unset($this->cart[$foodItemId]);
    }

    #[On('shortcut-clear-cart')]
    public function clearCart(): void
    {
        $this->reset(
            'cart', 'error', 'success', 'discountInput', 'notesInput',
            'tableNumberInput', 'campaignInput', 'tenderedInput', 'splitLegs',
            'upiRefInput', 'deliveryAddressInput', 'deliveryAgentInput'
        );
        $this->paymentMode = 'cash';
        $this->orderType = 'takeaway';
    }

    public function setPaymentMode(string $mode): void
    {
        if (in_array($mode, array_column(PaymentMode::cases(), 'value'), strict: true)) {
            $this->paymentMode = $mode;
            $this->error = null;
        }
    }

    // ---------------------------------------------------------------------
    // Live totals
    // ---------------------------------------------------------------------

    #[Computed]
    public function cartTotal(): string
    {
        $total = '0';

        foreach ($this->cart as $line) {
            $total = bcadd($total, bcmul($line['price'], (string) $line['quantity'], 2), 2);
        }

        return $total;
    }

    #[Computed]
    public function cartCount(): int
    {
        return array_sum(array_column($this->cart, 'quantity'));
    }

    /**
     * Sanitized, clamped bill-level discount.
     *
     * Empty / non-numeric input resolves to "0.00" and the discount can never
     * exceed the subtotal — the same upper bound OrderService enforces server
     * side, so the displayed payable amount always matches the saved bill.
     */
    #[Computed]
    public function cartDiscount(): string
    {
        if (! is_numeric($this->discountInput)) {
            return '0.00';
        }

        $discount = number_format(max((float) $this->discountInput, 0), 2, '.', '');

        return bccomp($discount, $this->cartTotal, 2) > 0 ? $this->cartTotal : $discount;
    }

    /**
     * Provisional amount payable after the discount. The server applies the
     * final Indian rupee round-off inside OrderService at checkout.
     */
    #[Computed]
    public function cartGrandTotal(): string
    {
        return bcsub($this->cartTotal, $this->cartDiscount, 2);
    }

    // ---------------------------------------------------------------------
    // Keyboard shortcut wiring (Livewire events)
    // ---------------------------------------------------------------------

    #[On('shortcut-cash')]
    public function checkoutViaCash(): void
    {
        $this->checkout(PaymentMode::Cash->value);
    }

    #[On('shortcut-upi')]
    public function checkoutViaUpi(): void
    {
        $this->checkout(PaymentMode::Upi->value);
    }

    #[On('shortcut-card')]
    public function checkoutViaCard(): void
    {
        $this->checkout(PaymentMode::Card->value);
    }

    public function setOrderType(string $type): void
    {
        if (in_array($type, array_column(OrderType::cases(), 'value'), strict: true)) {
            $this->orderType = $type;
            $this->error = null;
        }
    }

    /** Add one split-tender leg (mode + amount) for split checkout. */
    public function addSplitLeg(string $mode, string $amount): void
    {
        $mode = strtolower(trim($mode));

        if (! in_array($mode, array_column(PaymentMode::cases(), 'value'), strict: true)) {
            $this->error = 'Unknown payment mode for split leg.';

            return;
        }

        if (! is_numeric($amount) || (float) $amount <= 0) {
            $this->error = 'Split amount must be greater than zero.';

            return;
        }

        $this->splitLegs[] = [
            'mode' => $mode,
            'amount' => number_format((float) $amount, 2, '.', ''),
        ];
        $this->error = null;
    }

    public function removeSplitLeg(int $index): void
    {
        unset($this->splitLegs[$index]);
        $this->splitLegs = array_values($this->splitLegs);
    }

    /** Provisional split-leg total (server re-validates at checkout). */
    #[Computed]
    public function splitTotal(): string
    {
        $sum = '0.00';
        foreach ($this->splitLegs as $leg) {
            if (isset($leg['amount']) && is_numeric($leg['amount'])) {
                $sum = bcadd($sum, number_format((float) $leg['amount'], 2, '.', ''), 2);
            }
        }

        return $sum;
    }

    /** Provisional change due for cash checkout (server recomputes). */
    #[Computed]
    public function changeDue(): string
    {
        if (! is_numeric($this->tenderedInput)) {
            return '0.00';
        }
        $tendered = number_format(max((float) $this->tenderedInput, 0), 2, '.', '');
        if (bccomp($tendered, $this->cartGrandTotal, 2) < 0) {
            return '0.00';
        }

        return bcsub($tendered, $this->cartGrandTotal, 2);
    }

    // ---------------------------------------------------------------------
    // Checkout — commit via the SHARED OrderService, then print
    // ---------------------------------------------------------------------

    public function checkout(string $mode): void
    {
        $this->authorize('place-orders');

        $this->reset('error', 'success');

        if ($this->cart === []) {
            $this->error = 'Cart is empty — add items before settling.';

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        try {
            $modeEnum = PaymentMode::tryFrom($mode) ?? PaymentMode::Cash;
            $typeEnum = OrderType::tryFrom($this->orderType) ?? OrderType::Takeaway;

            $receipt = $this->orders->place($user, [
                'items' => array_map(
                    fn (array $line): array => [
                        'food_item_id' => $line['id'],
                        'quantity' => $line['quantity'],
                    ],
                    array_values($this->cart)
                ),
                'payment_mode' => $modeEnum->value,
                'order_type' => $typeEnum->value,
                'discount_amount' => $this->cartDiscount,
                'notes' => substr(trim($this->notesInput), 0, 200) ?: null,
                'table_number' => substr(trim($this->tableNumberInput), 0, 20) ?: null,
                'campaign_code' => substr(trim($this->campaignInput), 0, 40) ?: null,
                'tendered_amount' => is_numeric($this->tenderedInput) ? $this->tenderedInput : '0.00',
                'split_details' => $modeEnum === PaymentMode::Split ? $this->splitLegs : null,
                'upi_ref' => substr(trim($this->upiRefInput), 0, 60) ?: null,
                'delivery_address' => substr(trim($this->deliveryAddressInput), 0, 255) ?: null,
                'delivery_agent' => substr(trim($this->deliveryAgentInput), 0, 80) ?: null,
            ]);
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        // Update the DOM with the printed receipt data FIRST...
        $this->lastReceipt = $receipt->toArray();
        $this->success = sprintf('Bill %s settled — ₹%s', $receipt->order->order_number, $receipt->totalAmount);

        // ...then reset the cart for the next customer...
        $this->reset(
            'cart', 'discountInput', 'notesInput', 'tableNumberInput',
            'campaignInput', 'tenderedInput', 'splitLegs', 'upiRefInput',
            'deliveryAddressInput', 'deliveryAgentInput'
        );
        $this->paymentMode = PaymentMode::Cash->value;
        $this->orderType = OrderType::Takeaway->value;

        // ...and fire the browser event that triggers the native print dialog.
        $this->dispatch('trigger-print');
    }

    public function render()
    {
        return view('livewire.pos.billing-dashboard');
    }
}
