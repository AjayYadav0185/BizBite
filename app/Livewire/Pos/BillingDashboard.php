<?php

namespace App\Livewire\Pos;

use App\Models\Category;
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

    /** Payment mode for the next settlement: 'cash'|'upi'. */
    public string $paymentMode = 'cash';

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
        $this->reset('cart', 'error', 'success', 'discountInput', 'notesInput');
        $this->paymentMode = 'cash';
    }

    public function setPaymentMode(string $mode): void
    {
        if (in_array($mode, ['cash', 'upi'], strict: true)) {
            $this->paymentMode = $mode;
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
            $receipt = $this->orders->place($user, [
                'items' => array_map(
                    fn (array $line): array => [
                        'food_item_id' => $line['id'],
                        'quantity' => $line['quantity'],
                    ],
                    array_values($this->cart)
                ),
                'payment_mode' => $mode,
                // MVP scope §6: bill-level discount + free-text note travel
                // with the cart; OrderService re-validates both server side.
                'discount_amount' => $this->cartDiscount,
                'notes' => substr(trim($this->notesInput), 0, 200) ?: null,
            ]);
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        // Update the DOM with the printed receipt data FIRST...
        $this->lastReceipt = $receipt->toArray();
        $this->success = sprintf('Bill %s settled — ₹%s', $receipt->order->order_number, $receipt->totalAmount);

        // ...then reset the cart for the next customer...
        $this->reset('cart', 'discountInput', 'notesInput');
        $this->paymentMode = PaymentMode::Cash->value;

        // ...and fire the browser event that triggers the native print dialog.
        $this->dispatch('trigger-print');
    }

    public function render()
    {
        return view('livewire.pos.billing-dashboard');
    }
}
