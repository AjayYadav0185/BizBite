<?php

namespace App\Livewire\Pos;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Enums\OrderType;
use App\Models\Enums\PaymentMode;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Exceptions\OrderPlacementException;
use App\Services\OrderReceipt;
use App\Services\OrderService;
use App\Support\India;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * STAFF POS PORTAL — high-speed billing screen.
 *
 * The cart lives in reactive public properties; checkout delegates to the
 * shared {@see OrderService} (same engine as the API / mobile app).
 *
 * Keyboard-first:
 *   F2 clear · F4 search · F6 hold bill · F8 cash · F9 UPI · Esc close panels
 *
 * Counter extras for Indian QSRs / restaurants:
 *   - customer name + mobile on the bill (WhatsApp e-bill share)
 *   - hold / resume bills (parked per cashier, survive a page reload)
 *   - dynamic UPI QR with the exact payable amount
 *   - GST tax invoice + KOT printing, reprint of today's bills
 */
#[Layout('layouts.app')]
#[Title('Billing')]
final class BillingDashboard extends Component
{
    private const MAX_HELD = 20;

    public ?int $activeCategoryId = null;

    public string $search = '';

    /** @var array<int, array{id: int, name: string, price: string, quantity: int}> */
    public array $cart = [];

    public string $paymentMode = 'cash';

    public string $orderType = 'takeaway';

    public string $tableNumberInput = '';

    public string $campaignInput = '';

    public string $tenderedInput = '';

    /** @var array<int, array{mode: string, amount: string}> */
    public array $splitLegs = [];

    public string $upiRefInput = '';

    public string $deliveryAddressInput = '';

    public string $deliveryAgentInput = '';

    public string $customerNameInput = '';

    public string $customerPhoneInput = '';

    public string $discountInput = '';

    public string $notesInput = '';

    /** Receipt payload of the last settled / reprinted bill. */
    public ?array $lastReceipt = null;

    /** Show the post-checkout / reprint panel. */
    public bool $showReceipt = false;

    /** Right-hand drawer: null | 'held' | 'recent'. */
    public ?string $drawer = null;

    public ?string $error = null;

    public ?string $success = null;

    private OrderService $orders;

    /** Livewire re-hydrates via boot(), not mount(), on every request. */
    public function boot(OrderService $orders): void
    {
        $this->orders = $orders;
    }

    public function mount(): void
    {
        $this->authorize('access-pos-portal');
    }

    // ---------------------------------------------------------------------
    // Menu (tenant scoped automatically via StoreScope)
    // ---------------------------------------------------------------------

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    #[Computed]
    public function menuItems(): Collection
    {
        $term = trim($this->search);

        return FoodItem::query()
            ->with('category:id,name')
            ->where('is_available', true)
            ->when($this->activeCategoryId, fn ($q, $id) => $q->where('category_id', $id))
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit(120)
            ->get();
    }

    #[Computed]
    public function store()
    {
        return Auth::user()?->store;
    }

    // ---------------------------------------------------------------------
    // Cart
    // ---------------------------------------------------------------------

    public function addItem(int $foodItemId): void
    {
        /** @var FoodItem|null $item */
        $item = FoodItem::query()->where('is_available', true)->find($foodItemId);

        if ($item === null) {
            $this->error = 'That item is no longer available.';

            return;
        }

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

    /** Enter in the search box adds the first match — barcode / SKU friendly. */
    public function addFirstMatch(): void
    {
        $first = $this->menuItems->first(fn (FoodItem $i) => ! $i->isOutOfStock());

        if ($first === null) {
            $this->error = 'No item matches "'.trim($this->search).'".';

            return;
        }

        $this->addItem($first->id);
        $this->search = '';
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
        $this->resetBillInputs();
        $this->reset('error', 'success');
    }

    private function resetBillInputs(): void
    {
        $this->reset(
            'cart', 'discountInput', 'notesInput', 'tableNumberInput', 'campaignInput',
            'tenderedInput', 'splitLegs', 'upiRefInput', 'deliveryAddressInput',
            'deliveryAgentInput', 'customerNameInput', 'customerPhoneInput'
        );
        $this->paymentMode = PaymentMode::Cash->value;
        $this->orderType = OrderType::Takeaway->value;
    }

    public function setPaymentMode(string $mode): void
    {
        if (in_array($mode, array_column(PaymentMode::cases(), 'value'), strict: true)) {
            $this->paymentMode = $mode;
            $this->error = null;
        }
    }

    public function setOrderType(string $type): void
    {
        if (in_array($type, array_column(OrderType::cases(), 'value'), strict: true)) {
            $this->orderType = $type;
            $this->error = null;
        }
    }

    /** Quick-cash denominations (₹ notes) fill the tendered field. */
    public function setTendered(string $amount): void
    {
        if (is_numeric($amount) && (float) $amount >= 0) {
            $this->tenderedInput = number_format((float) $amount, 2, '.', '');
        }
    }

    public function openDrawer(?string $drawer): void
    {
        $this->drawer = in_array($drawer, ['held', 'recent'], true) && $this->drawer !== $drawer ? $drawer : null;
    }

    // ---------------------------------------------------------------------
    // Totals
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

    /** Manual bill discount, sanitised and clamped to the subtotal. */
    #[Computed]
    public function cartDiscount(): string
    {
        if (! is_numeric($this->discountInput)) {
            return '0.00';
        }

        $discount = number_format(max((float) $this->discountInput, 0), 2, '.', '');

        return bccomp($discount, $this->cartTotal, 2) > 0 ? $this->cartTotal : $discount;
    }

    /** Subtotal less the manual discount (before campaign + round-off). */
    #[Computed]
    public function cartGrandTotal(): string
    {
        return bcsub($this->cartTotal, $this->cartDiscount, 2);
    }

    /**
     * Preview of the campaign discount, using the same Campaign::discountFor()
     * the server applies at checkout, capped to what remains after the manual
     * discount — so the screen total equals the printed total.
     */
    #[Computed]
    public function campaignDiscount(): string
    {
        $code = strtoupper(trim($this->campaignInput));

        if ($code === '' || $this->cart === []) {
            return '0.00';
        }

        $campaign = Campaign::query()->where('code', $code)->first();
        if ($campaign === null) {
            return '0.00';
        }

        $discount = $campaign->discountFor($this->cartTotal);
        $room = $this->cartGrandTotal;

        return bccomp($discount, $room, 2) > 0 ? $room : $discount;
    }

    #[Computed]
    public function campaignStatus(): ?string
    {
        if (trim($this->campaignInput) === '' || $this->cart === []) {
            return null;
        }

        return bccomp($this->campaignDiscount, '0', 2) > 0 ? 'applied' : 'invalid';
    }

    /** Final amount to collect — rounded to the rupee exactly like OrderService. */
    #[Computed]
    public function payable(): string
    {
        $after = bcsub($this->cartGrandTotal, $this->campaignDiscount, 2);

        return number_format(round((float) $after), 2, '.', '');
    }

    #[Computed]
    public function roundOff(): string
    {
        return bcsub($this->payable, bcsub($this->cartGrandTotal, $this->campaignDiscount, 2), 2);
    }

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

    #[Computed]
    public function changeDue(): string
    {
        if (! is_numeric($this->tenderedInput)) {
            return '0.00';
        }
        $tendered = number_format(max((float) $this->tenderedInput, 0), 2, '.', '');
        if (bccomp($tendered, $this->payable, 2) < 0) {
            return '0.00';
        }

        return bcsub($tendered, $this->payable, 2);
    }

    /** Dynamic UPI deep link for the QR (null when no VPA is configured). */
    #[Computed]
    public function upiLink(): ?string
    {
        $vpa = $this->store?->upi_vpa;

        if (blank($vpa) || $this->cart === [] || bccomp($this->payable, '0', 2) <= 0) {
            return null;
        }

        return India::upiLink($vpa, (string) $this->store->name, $this->payable, 'BizBite bill');
    }

    // ---------------------------------------------------------------------
    // Split tender
    // ---------------------------------------------------------------------

    public function addSplitLeg(string $mode, string $amount): void
    {
        $mode = strtolower(trim($mode));

        if (! in_array($mode, array_column(PaymentMode::cases(), 'value'), strict: true) || $mode === 'split') {
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

    /** Fill the remaining split balance in one tap. */
    public function addSplitRemainder(string $mode): void
    {
        $remaining = bcsub($this->payable, $this->splitTotal, 2);

        if (bccomp($remaining, '0', 2) <= 0) {
            $this->error = 'Split legs already cover the bill.';

            return;
        }

        $this->addSplitLeg($mode, $remaining);
    }

    public function removeSplitLeg(int $index): void
    {
        unset($this->splitLegs[$index]);
        $this->splitLegs = array_values($this->splitLegs);
    }

    // ---------------------------------------------------------------------
    // Hold / resume (parked bills, per cashier, kept 24h)
    // ---------------------------------------------------------------------

    private function heldKey(): string
    {
        $user = Auth::user();

        return 'pos:held:'.$user?->store_id.':'.$user?->id;
    }

    /** @return array<string, array<string, mixed>> */
    #[Computed]
    public function heldBills(): array
    {
        return Cache::get($this->heldKey(), []);
    }

    #[On('shortcut-hold')]
    public function holdBill(): void
    {
        if ($this->cart === []) {
            $this->error = 'Nothing to hold — the cart is empty.';

            return;
        }

        $held = $this->heldBills;

        if (count($held) >= self::MAX_HELD) {
            $this->error = 'Too many bills on hold ('.self::MAX_HELD.'). Settle or discard some first.';

            return;
        }

        $id = (string) Str::ulid();
        $label = collect([
            filled($this->tableNumberInput) ? 'Table '.trim($this->tableNumberInput) : null,
            filled($this->customerNameInput) ? trim($this->customerNameInput) : null,
        ])->filter()->implode(' · ') ?: 'Bill '.(count($held) + 1);

        $held[$id] = [
            'id' => $id,
            'label' => $label,
            'held_at' => now()->format('h:i A'),
            'total' => $this->payable,
            'count' => $this->cartCount,
            'state' => $this->only([
                'cart', 'orderType', 'tableNumberInput', 'campaignInput', 'discountInput', 'notesInput',
                'customerNameInput', 'customerPhoneInput', 'deliveryAddressInput', 'deliveryAgentInput',
            ]),
        ];

        Cache::put($this->heldKey(), $held, now()->addDay());
        unset($this->heldBills);

        $this->resetBillInputs();
        $this->error = null;
        $this->success = 'Bill held: '.$label.'. Resume it from "Held".';
    }

    public function resumeBill(string $id): void
    {
        $held = $this->heldBills;

        if (! isset($held[$id])) {
            $this->error = 'That held bill is no longer available.';

            return;
        }

        // Park whatever is on screen so nothing is ever lost on a swap.
        if ($this->cart !== []) {
            $this->holdBill();
            $held = $this->heldBills;
        }

        $state = $held[$id]['state'];
        $label = $held[$id]['label'];
        unset($held[$id]);
        Cache::put($this->heldKey(), $held, now()->addDay());
        unset($this->heldBills);

        foreach ($state as $key => $value) {
            $this->{$key} = $value;
        }

        $this->drawer = null;
        $this->error = null;
        $this->success = 'Resumed '.$label.'.';
    }

    public function discardHeldBill(string $id): void
    {
        $held = $this->heldBills;
        unset($held[$id]);
        Cache::put($this->heldKey(), $held, now()->addDay());
        unset($this->heldBills);
    }

    // ---------------------------------------------------------------------
    // Recent bills + reprint
    // ---------------------------------------------------------------------

    #[Computed]
    public function recentBills(): Collection
    {
        return Order::query()
            ->whereDate('created_at', today())
            ->latest('id')
            ->limit(25)
            ->get(['id', 'order_number', 'total_amount', 'payment_mode', 'status', 'customer_name', 'table_number', 'created_at']);
    }

    public function reprint(int $orderId, string $copy = 'receipt'): void
    {
        $order = Order::query()->find($orderId);

        if ($order === null) {
            $this->error = 'Bill not found.';

            return;
        }

        $this->lastReceipt = OrderReceipt::forOrder($order)->toArray() + ['reprint' => true];
        $this->showReceipt = true;
        $this->drawer = null;
        $this->dispatch('trigger-print', copy: $copy === 'kot' ? 'kot' : 'receipt');
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
    }

    /** wa.me link that sends the e-bill to the customer's WhatsApp. */
    #[Computed]
    public function whatsappLink(): ?string
    {
        $r = $this->lastReceipt;
        if ($r === null) {
            return null;
        }

        $lines = ['*'.($r['store']['name'] ?? 'BizBite').'*', 'Bill: '.$r['order_number'].' · '.$r['placed_at'], ''];
        foreach ($r['items'] as $item) {
            $lines[] = $item['quantity'].' x '.$item['food_item_name'].' — ₹'.India::number($item['subtotal']);
        }
        if (bccomp((string) ($r['discount_amount'] ?? '0'), '0', 2) > 0) {
            $lines[] = 'Discount: -₹'.India::number($r['discount_amount']);
        }
        $lines[] = '*Total: ₹'.India::number($r['total_amount']).'* ('.strtoupper((string) $r['payment_mode']).')';
        if (filled($r['store']['gstin'] ?? null)) {
            $lines[] = 'GSTIN: '.$r['store']['gstin'];
        }
        $lines[] = '';
        $lines[] = filled($r['store']['print_footer'] ?? null) ? $r['store']['print_footer'] : 'Thank you! Visit again.';

        $phone = India::normaliseMobile($r['customer_phone'] ?? null);

        return 'https://wa.me/'.($phone ? '91'.$phone : '').'?text='.rawurlencode(implode("\n", $lines));
    }

    // ---------------------------------------------------------------------
    // Checkout
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

    public function checkout(string $mode): void
    {
        $this->authorize('place-orders');

        $this->reset('error', 'success');

        if ($this->cart === []) {
            $this->error = 'Cart is empty — add items before settling.';

            return;
        }

        $phone = null;
        if (trim($this->customerPhoneInput) !== '') {
            $phone = India::normaliseMobile($this->customerPhoneInput);
            if ($phone === null) {
                $this->error = 'Customer mobile must be a valid 10-digit Indian number.';

                return;
            }
        }

        $modeEnum = PaymentMode::tryFrom($mode) ?? PaymentMode::Cash;
        $typeEnum = OrderType::tryFrom($this->orderType) ?? OrderType::Takeaway;

        if ($modeEnum === PaymentMode::Credit && blank(trim($this->customerNameInput)) && $phone === null) {
            $this->error = 'Credit (pay later) bills need the customer name or mobile.';

            return;
        }

        if ($typeEnum === OrderType::DineIn && blank(trim($this->tableNumberInput))) {
            $this->error = 'Enter the table number for a dine-in bill.';

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        try {
            $receipt = $this->orders->place($user, [
                'items' => array_map(
                    fn (array $line): array => ['food_item_id' => $line['id'], 'quantity' => $line['quantity']],
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
                'customer_name' => substr(trim($this->customerNameInput), 0, 80) ?: null,
                'customer_phone' => $phone,
            ]);
        } catch (OrderPlacementException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->lastReceipt = $receipt->toArray();
        $this->showReceipt = true;
        $this->success = sprintf('Bill %s settled — %s', $receipt->order->order_number, India::money($receipt->totalAmount));

        $this->resetBillInputs();
        unset($this->recentBills);

        $this->dispatch('trigger-print', copy: 'receipt');
    }

    public function render()
    {
        return view('livewire.pos.billing-dashboard');
    }
}
