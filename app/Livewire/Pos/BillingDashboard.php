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

    /** Shared checkout service (also used by the Flutter API controller). */
    private OrderService $orders;

    /**
     * Inject the SINGLE shared OrderService here — never duplicate its logic.
     */
    public function mount(OrderService $orders): void
    {
        $this->authorize('access-pos-portal');

        $this->orders = $orders;
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
        /** @var \App\Models\FoodItem|null $item */
        $item = FoodItem::query()->where('is_available', true)->find($foodItemId);

        if ($item === null) {
            $this->error = 'That item is no longer available.';

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
        if (isset($this->cart[$foodItemId])) {
            $this->cart[$foodItemId]['quantity']++;
        }
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
        $this->reset('cart', 'error', 'success');
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

        /** @var \App\Models\User $user */
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
            ]);
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        // Update the DOM with the printed receipt data FIRST...
        $this->lastReceipt = $receipt->toArray();
        $this->success = sprintf('Bill %s settled — ₹%s', $receipt->order->order_number, $receipt->totalAmount);

        // ...then reset the cart for the next customer...
        $this->reset('cart');
        $this->paymentMode = PaymentMode::Cash->value;

        // ...and fire the browser event that triggers the native print dialog.
        $this->dispatch('trigger-print');
    }

    public function render()
    {
        return view('livewire.pos.billing-dashboard');
    }
}