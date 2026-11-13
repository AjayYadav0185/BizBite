{{-- =====================================================================
     BizBite — STAFF POS: billing screen
     Menu grid (left) · live bill (right) · held / recent drawer ·
     settle panel with GST invoice, KOT and WhatsApp e-bill.
====================================================================== --}}
@php
    $money = fn ($v) => \App\Support\India::money($v);
    $orderTypes = ['takeaway' => 'Takeaway', 'dine_in' => 'Dine-in', 'parcel' => 'Parcel', 'delivery' => 'Delivery'];
    $payModes = ['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'credit' => 'Credit', 'split' => 'Split'];
    $foodDot = ['veg' => 'border-emerald-600 text-emerald-600', 'non_veg' => 'border-red-600 text-red-600', 'egg' => 'border-amber-500 text-amber-500'];
    $field = 'w-full rounded-lg border border-line bg-surface-2 px-3 py-2 text-sm text-ink placeholder:text-ink-3 outline-none transition focus:border-brand-500 focus:bg-surface focus:ring-2 focus:ring-brand-500/20';
    $label = 'mb-1 block text-[11px] font-semibold uppercase tracking-wider text-ink-3';
    $held = $this->heldBills;
@endphp
<div class="flex h-[100dvh] flex-col overflow-hidden" x-data="{ more: false }">
    <style>
        @media print {
            @page { margin: 2mm; size: 80mm auto; }
            html, body { background: #fff !important; }
            body * { visibility: hidden !important; }
            body[data-print="receipt"] #print-receipt, body[data-print="receipt"] #print-receipt *,
            body[data-print="kot"] #print-kot, body[data-print="kot"] #print-kot * { visibility: visible !important; }
            body[data-print="receipt"] #print-receipt, body[data-print="kot"] #print-kot {
                display: block !important; position: absolute !important; left: 0; top: 0; width: 72mm !important;
            }
        }
    </style>

    @include('partials.pos-nav', ['active' => 'billing'])

    <div class="flex min-h-0 flex-1 print:hidden">
        {{-- =========================================== MENU --}}
        <section class="flex min-w-0 flex-1 flex-col">
            <div class="space-y-3 border-b border-line bg-surface px-3 py-3 sm:px-4">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-3" />
                        <input id="pos-search" type="search" autocomplete="off"
                               placeholder="Search item… Enter to add (F4)"
                               wire:model.live.debounce.250ms="search"
                               wire:keydown.enter.prevent="addFirstMatch"
                               class="{{ $field }} h-10 pl-9" />
                    </div>
                    <button wire:click="openDrawer('held')"
                            @class(['relative flex h-10 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition',
                                $drawer === 'held' ? 'border-brand-500 bg-brand-soft text-brand-ink' : 'border-line text-ink-2 hover:border-brand-400'])>
                        <x-icon name="pause" class="h-4 w-4" /><span class="hidden sm:inline">Held</span>
                        @if (count($held))
                            <span class="grid h-5 min-w-5 place-items-center rounded-full bg-accent-500 px-1 text-[10px] font-bold text-white">{{ count($held) }}</span>
                        @endif
                    </button>
                    <button wire:click="openDrawer('recent')"
                            @class(['flex h-10 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition',
                                $drawer === 'recent' ? 'border-brand-500 bg-brand-soft text-brand-ink' : 'border-line text-ink-2 hover:border-brand-400'])>
                        <x-icon name="history" class="h-4 w-4" /><span class="hidden sm:inline">Recent</span>
                    </button>
                </div>

                <div class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-0.5" role="tablist">
                    <button wire:click="$set('activeCategoryId', null)"
                            @class(['shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition',
                                $activeCategoryId === null ? 'bg-brand-600 text-white shadow-sm' : 'bg-surface-2 text-ink-2 hover:bg-surface-3'])>
                        All items
                    </button>
                    @foreach ($this->categories as $category)
                        <button wire:key="cat-{{ $category->id }}" wire:click="$set('activeCategoryId', {{ $category->id }})"
                                @class(['shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition',
                                    $activeCategoryId === $category->id ? 'bg-brand-600 text-white shadow-sm' : 'bg-surface-2 text-ink-2 hover:bg-surface-3'])>
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($error || $success)
                <div class="px-3 pt-3 sm:px-4">
                    @if ($error)
                        <div class="flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-300" role="alert">
                            <x-icon name="alert" class="h-4 w-4 shrink-0" /><span class="flex-1">{{ $error }}</span>
                            <button wire:click="$set('error', null)" class="opacity-60 hover:opacity-100"><x-icon name="x" class="h-4 w-4" /></button>
                        </div>
                    @elseif ($success)
                        <div class="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-300" role="status">
                            <x-icon name="check" class="h-4 w-4 shrink-0" /><span class="flex-1">{{ $success }}</span>
                            <button wire:click="$set('success', null)" class="opacity-60 hover:opacity-100"><x-icon name="x" class="h-4 w-4" /></button>
                        </div>
                    @endif
                </div>
            @endif

            <div class="min-h-0 flex-1 overflow-y-auto p-3 pb-24 sm:p-4 lg:pb-4">
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                    @forelse ($this->menuItems as $item)
                        @php
                            $out = $item->isOutOfStock();
                            $qty = $cart[$item->id]['quantity'] ?? 0;
                            $type = $item->food_type?->value;
                        @endphp
                        <button wire:key="menu-{{ $item->id }}"
                                @if ($out) disabled @else wire:click="addItem({{ $item->id }})" @endif
                                @class(['group relative flex min-h-[92px] flex-col rounded-xl border p-3 text-left transition',
                                    'cursor-not-allowed border-line bg-surface-2 opacity-60' => $out,
                                    'border-brand-500 bg-brand-soft ring-1 ring-brand-500/40' => ! $out && $qty > 0,
                                    'border-line bg-surface hover:-translate-y-px hover:border-brand-400 hover:shadow-card-hover active:translate-y-0 active:scale-[.98]' => ! $out && $qty === 0])>
                            <div class="flex items-start gap-2">
                                @if ($type)
                                    <span class="mt-0.5 grid h-3.5 w-3.5 shrink-0 place-items-center rounded-[3px] border-[1.5px] {{ $foodDot[$type] ?? '' }}" title="{{ str_replace('_', '-', $type) }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    </span>
                                @endif
                                <span class="line-clamp-2 text-sm font-semibold leading-snug">{{ $item->name }}</span>
                            </div>
                            <div class="mt-auto flex items-end justify-between gap-1 pt-2">
                                <span @class(['font-display text-[15px] font-semibold', $out ? 'text-ink-3 line-through' : 'text-ink'])>{{ $money($item->price) }}</span>
                                @if ($out)
                                    <span class="rounded bg-red-500/10 px-1.5 py-0.5 text-[10px] font-bold uppercase text-red-600 dark:text-red-300">Sold out</span>
                                @elseif ($item->isLowStock())
                                    <span class="rounded bg-accent-500/10 px-1.5 py-0.5 text-[10px] font-bold text-accent-600 dark:text-accent-400">{{ $item->stock_quantity }} left</span>
                                @endif
                            </div>
                            @if ($qty > 0)
                                <span class="absolute -right-1.5 -top-1.5 grid h-6 min-w-6 place-items-center rounded-full bg-brand-600 px-1.5 text-xs font-bold text-white shadow">{{ $qty }}</span>
                            @endif
                        </button>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-line-strong p-12 text-center">
                            <x-icon name="search" class="mx-auto h-8 w-8 text-ink-3" />
                            <p class="mt-2 text-sm text-ink-3">No items match. Try another search or category.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- =========================================== BILL --}}
        <aside id="pos-cart"
               x-data="{ open: false }" x-on:open-cart.window="open = true"
               :class="open ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
               class="fixed inset-x-0 bottom-0 top-14 z-40 flex translate-y-full flex-col border-l border-line bg-surface shadow-pop transition-transform duration-200 lg:static lg:z-auto lg:w-[400px] lg:translate-y-0 lg:shadow-none xl:w-[430px]">

            {{-- order type --}}
            <div class="flex items-center gap-2 border-b border-line px-3 py-2.5">
                <button @click="open = false" class="grid h-8 w-8 place-items-center rounded-lg text-ink-3 hover:bg-surface-2 lg:hidden"><x-icon name="x" class="h-4 w-4" /></button>
                <div class="grid flex-1 grid-cols-4 gap-1 rounded-lg bg-surface-2 p-1" role="group" aria-label="Order type">
                    @foreach ($orderTypes as $value => $text)
                        <button wire:click="setOrderType('{{ $value }}')"
                                @class(['rounded-md py-1.5 text-xs font-semibold transition',
                                    $orderType === $value ? 'bg-surface text-brand-ink shadow-card' : 'text-ink-3 hover:text-ink'])>{{ $text }}</button>
                    @endforeach
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                {{-- customer / table --}}
                <div class="grid grid-cols-2 gap-2 px-3 pt-3">
                    <input type="text" maxlength="80" placeholder="Customer name" wire:model.blur="customerNameInput" class="{{ $field }}" />
                    <input type="tel" inputmode="numeric" maxlength="14" placeholder="Mobile (WhatsApp)" wire:model.blur="customerPhoneInput" class="{{ $field }}" />
                    @if ($orderType === 'dine_in')
                        <input type="text" maxlength="20" placeholder="Table no. *" wire:model.live.debounce.400ms="tableNumberInput" class="{{ $field }} col-span-2" />
                    @endif
                    @if ($orderType === 'delivery')
                        <input type="text" maxlength="255" placeholder="Delivery address" wire:model.blur="deliveryAddressInput" class="{{ $field }} col-span-2" />
                        <input type="text" maxlength="80" placeholder="Rider name" wire:model.blur="deliveryAgentInput" class="{{ $field }} col-span-2" />
                    @endif
                </div>

                {{-- lines --}}
                <div class="mt-3 flex items-center justify-between px-3">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-ink-3">Current bill · {{ $this->cartCount }} items</h2>
                    @if ($cart !== [])
                        <button wire:click="clearCart" wire:confirm="Clear this bill?" class="text-xs font-semibold text-ink-3 hover:text-red-500">Clear (F2)</button>
                    @endif
                </div>
                <ul class="mt-1 divide-y divide-line px-3">
                    @forelse ($cart as $line)
                        <li wire:key="cart-{{ $line['id'] }}" class="flex items-center gap-2 py-2.5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $line['name'] }}</p>
                                <p class="text-xs text-ink-3">{{ $money($line['price']) }}</p>
                            </div>
                            <div class="flex items-center rounded-lg border border-line">
                                <button wire:click="decrementQuantity({{ $line['id'] }})" class="grid h-8 w-8 place-items-center text-ink-2 hover:text-red-500" aria-label="Less"><x-icon name="minus" class="h-4 w-4" /></button>
                                <span class="w-7 text-center text-sm font-bold tabular-nums">{{ $line['quantity'] }}</span>
                                <button wire:click="incrementQuantity({{ $line['id'] }})" class="grid h-8 w-8 place-items-center text-ink-2 hover:text-brand-ink" aria-label="More"><x-icon name="plus" class="h-4 w-4" /></button>
                            </div>
                            <p class="w-20 text-right text-sm font-semibold tabular-nums">{{ $money(bcmul($line['price'], (string) $line['quantity'], 2)) }}</p>
                            <button wire:click="removeItem({{ $line['id'] }})" class="text-ink-3 hover:text-red-500" title="Remove"><x-icon name="trash" class="h-4 w-4" /></button>
                        </li>
                    @empty
                        <li class="py-10 text-center">
                            <x-icon name="receipt" class="mx-auto h-10 w-10 text-line-strong" />
                            <p class="mt-2 text-sm text-ink-3">Tap items to start a bill</p>
                            <p class="mt-1 text-xs text-ink-3">F4 search · F6 hold · F8 cash · F9 UPI</p>
                        </li>
                    @endforelse
                </ul>

                {{-- discount / campaign / note --}}
                <div class="px-3 pb-3">
                    <button @click="more = !more" class="flex w-full items-center justify-between rounded-lg py-2 text-xs font-semibold text-brand-ink">
                        <span>Discount, coupon &amp; note</span>
                        <span x-text="more ? '−' : '+'"></span>
                    </button>
                    <div x-show="more" x-cloak class="grid grid-cols-2 gap-2">
                        <label class="block"><span class="{{ $label }}">Discount ₹</span>
                            <input type="number" min="0" step="0.01" inputmode="decimal" placeholder="0" wire:model.live.debounce.400ms="discountInput" class="{{ $field }}" /></label>
                        <label class="block"><span class="{{ $label }}">Coupon code</span>
                            <input type="text" maxlength="40" placeholder="DIWALI10" wire:model.live.debounce.500ms="campaignInput" class="{{ $field }} uppercase" /></label>
                        @if ($this->campaignStatus === 'invalid')
                            <p class="col-span-2 -mt-1 text-xs font-medium text-red-500">Coupon not valid for this bill.</p>
                        @endif
                        <label class="col-span-2 block"><span class="{{ $label }}">Kitchen / bill note</span>
                            <input type="text" maxlength="200" placeholder="e.g. less spicy, no onion" wire:model.blur="notesInput" class="{{ $field }}" /></label>
                    </div>
                </div>
            </div>

            {{-- totals + payment --}}
            <div class="border-t border-line bg-surface-2/60 px-3 pb-3 pt-2.5">
                <dl class="space-y-0.5 text-sm">
                    <div class="flex justify-between text-ink-2"><dt>Subtotal</dt><dd class="tabular-nums">{{ $money($this->cartTotal) }}</dd></div>
                    @if (bccomp($this->cartDiscount, '0', 2) > 0)
                        <div class="flex justify-between text-accent-600 dark:text-accent-400"><dt>Discount</dt><dd class="tabular-nums">−{{ $money($this->cartDiscount) }}</dd></div>
                    @endif
                    @if (bccomp($this->campaignDiscount, '0', 2) > 0)
                        <div class="flex justify-between text-accent-600 dark:text-accent-400"><dt>Coupon {{ strtoupper($campaignInput) }}</dt><dd class="tabular-nums">−{{ $money($this->campaignDiscount) }}</dd></div>
                    @endif
                    @if ($cart !== [] && bccomp($this->roundOff, '0', 2) !== 0)
                        <div class="flex justify-between text-xs text-ink-3"><dt>Round off</dt><dd class="tabular-nums">{{ $this->roundOff }}</dd></div>
                    @endif
                    @if ($this->store?->is_gst_enabled)
                        <div class="text-[11px] text-ink-3">Prices include GST</div>
                    @endif
                </dl>

                <div class="mt-2 grid grid-cols-5 gap-1" role="group" aria-label="Payment mode">
                    @foreach ($payModes as $value => $text)
                        <button wire:click="setPaymentMode('{{ $value }}')"
                                @class(['rounded-lg border py-2 text-xs font-semibold transition',
                                    $paymentMode === $value ? 'border-brand-600 bg-brand-600 text-white' : 'border-line bg-surface text-ink-2 hover:border-brand-400'])>{{ $text }}</button>
                    @endforeach
                </div>

                @if ($cart !== [])
                    @if ($paymentMode === 'cash')
                        <div class="mt-2 flex items-center gap-1.5">
                            <input type="number" min="0" step="1" inputmode="decimal" placeholder="Cash received"
                                   wire:model.live.debounce.300ms="tenderedInput" class="{{ $field }} h-9 flex-1" />
                            @foreach (array_unique([(float) $this->payable, ceil((float) $this->payable / 100) * 100, ceil((float) $this->payable / 500) * 500]) as $note)
                                @if ($note > 0)
                                    <button wire:click="setTendered('{{ $note }}')" class="h-9 shrink-0 rounded-lg border border-line bg-surface px-2 text-xs font-semibold text-ink-2 hover:border-brand-400">₹{{ (int) $note }}</button>
                                @endif
                            @endforeach
                        </div>
                        @if (bccomp($this->changeDue, '0', 2) > 0)
                            <p class="mt-1.5 flex justify-between rounded-lg bg-emerald-500/10 px-3 py-1.5 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                                <span>Return change</span><span class="tabular-nums">{{ $money($this->changeDue) }}</span>
                            </p>
                        @endif
                    @elseif ($paymentMode === 'upi')
                        <div class="mt-2 flex items-center gap-3 rounded-lg border border-line bg-surface p-2">
                            @if ($this->upiLink)
                                <div wire:key="qr-{{ md5($this->upiLink) }}" x-data x-init="$el.innerHTML = window.bbQrSvg(@js($this->upiLink))"
                                     class="h-28 w-28 shrink-0 rounded bg-white p-1 [&_svg]:h-full [&_svg]:w-full"></div>
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <p class="text-xs text-ink-3">Scan with any UPI app</p>
                                    <p class="font-display text-lg font-semibold">{{ $money($this->payable) }}</p>
                                    <p class="truncate text-[11px] text-ink-3">{{ $this->store->upi_vpa }}</p>
                                    <input type="text" maxlength="60" placeholder="UTR / ref (optional)" wire:model.blur="upiRefInput" class="{{ $field }} h-8 text-xs" />
                                </div>
                            @else
                                <div class="flex-1 space-y-1.5">
                                    <p class="text-xs text-ink-3">Add your UPI ID in Admin → Store settings to show a payment QR here.</p>
                                    <input type="text" maxlength="60" placeholder="UTR / ref (optional)" wire:model.blur="upiRefInput" class="{{ $field }} h-8 text-xs" />
                                </div>
                            @endif
                        </div>
                    @elseif ($paymentMode === 'split')
                        <div class="mt-2 rounded-lg border border-dashed border-line-strong bg-surface p-2" x-data="{ m: 'cash', a: '' }">
                            <div class="flex gap-1.5">
                                <select x-model="m" class="{{ $field }} h-8 w-24 px-2 py-0 text-xs">
                                    <option value="cash">Cash</option><option value="upi">UPI</option><option value="card">Card</option><option value="credit">Credit</option>
                                </select>
                                <input x-model="a" type="number" min="1" step="0.01" placeholder="₹ amount" class="{{ $field }} h-8 flex-1 text-xs" />
                                <button @click="if (a) { $wire.addSplitLeg(m, a); a = '' } else { $wire.addSplitRemainder(m) }"
                                        class="h-8 rounded-lg bg-brand-600 px-3 text-xs font-semibold text-white">Add</button>
                            </div>
                            @foreach ($splitLegs as $i => $leg)
                                <div class="mt-1.5 flex items-center justify-between text-xs">
                                    <span class="font-semibold uppercase">{{ $leg['mode'] }}</span>
                                    <span class="ml-auto mr-2 tabular-nums">{{ $money($leg['amount']) }}</span>
                                    <button wire:click="removeSplitLeg({{ $i }})" class="text-ink-3 hover:text-red-500"><x-icon name="x" class="h-3.5 w-3.5" /></button>
                                </div>
                            @endforeach
                            @php $remaining = bcsub($this->payable, $this->splitTotal, 2); @endphp
                            <p @class(['mt-1.5 text-[11px] font-semibold', bccomp($remaining, '0', 2) === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-3'])>
                                {{ bccomp($remaining, '0', 2) === 0 ? 'Fully covered ✓' : 'Remaining '.$money($remaining).' — empty amount adds the rest' }}
                            </p>
                        </div>
                    @elseif ($paymentMode === 'credit')
                        <p class="mt-2 rounded-lg bg-accent-500/10 px-3 py-1.5 text-xs font-medium text-accent-700 dark:text-accent-400">Pay-later bill — customer name or mobile required.</p>
                    @endif
                @endif

                <button wire:click="checkout('{{ $paymentMode }}')" wire:loading.attr="disabled" wire:target="checkout"
                        @disabled($cart === [])
                        class="mt-2.5 flex h-14 w-full items-center justify-between rounded-xl bg-brand-600 px-5 text-white shadow-sm transition hover:bg-brand-700 active:scale-[.99] disabled:cursor-not-allowed disabled:opacity-40">
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        <x-icon name="printer" class="h-5 w-5" />
                        <span wire:loading.remove wire:target="checkout">Settle &amp; print</span>
                        <span wire:loading wire:target="checkout">Saving…</span>
                    </span>
                    <span class="font-display text-xl font-semibold tabular-nums">{{ $money($this->payable) }}</span>
                </button>
                <div class="mt-1.5 flex justify-between text-[11px] text-ink-3">
                    <span>F8 cash · F9 UPI</span>
                    <button wire:click="holdBill" @disabled($cart === []) class="font-semibold hover:text-brand-ink disabled:opacity-40">Hold bill (F6)</button>
                </div>
            </div>
        </aside>
    </div>

    {{-- mobile bill bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 px-3 py-2.5 backdrop-blur print:hidden lg:hidden">
        <button x-on:click="$dispatch('open-cart')" class="flex h-12 w-full items-center justify-between rounded-xl bg-brand-600 px-4 text-white">
            <span class="text-sm font-semibold">{{ $this->cartCount }} items · View bill</span>
            <span class="font-display text-lg font-semibold">{{ $money($this->payable) }}</span>
        </button>
    </div>

    {{-- =========================================== DRAWER: held / recent --}}
    @if ($drawer)
        <div class="fixed inset-0 z-50 flex justify-end print:hidden" x-on:keydown.escape.window="$wire.openDrawer(null)">
            <div class="absolute inset-0 bg-black/40" wire:click="openDrawer(null)"></div>
            <div class="relative flex h-full w-full max-w-md flex-col bg-surface shadow-pop">
                <div class="flex items-center justify-between border-b border-line px-4 py-3">
                    <h2 class="font-display text-base font-semibold">{{ $drawer === 'held' ? 'Bills on hold' : "Today's bills" }}</h2>
                    <button wire:click="openDrawer(null)" class="grid h-8 w-8 place-items-center rounded-lg hover:bg-surface-2"><x-icon name="x" class="h-4 w-4" /></button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-3">
                    @if ($drawer === 'held')
                        @forelse ($held as $bill)
                            <div wire:key="held-{{ $bill['id'] }}" class="mb-2 flex items-center gap-3 rounded-xl border border-line p-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold">{{ $bill['label'] }}</p>
                                    <p class="text-xs text-ink-3">{{ $bill['count'] }} items · held {{ $bill['held_at'] }}</p>
                                </div>
                                <span class="text-sm font-semibold tabular-nums">{{ $money($bill['total']) }}</span>
                                <button wire:click="resumeBill('{{ $bill['id'] }}')" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white">Resume</button>
                                <button wire:click="discardHeldBill('{{ $bill['id'] }}')" wire:confirm="Discard this held bill?" class="text-ink-3 hover:text-red-500"><x-icon name="trash" class="h-4 w-4" /></button>
                            </div>
                        @empty
                            <p class="py-12 text-center text-sm text-ink-3">No bills on hold. Press F6 to park the current bill.</p>
                        @endforelse
                    @else
                        @forelse ($this->recentBills as $bill)
                            <div wire:key="recent-{{ $bill->id }}" class="mb-2 flex items-center gap-3 rounded-xl border border-line p-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-mono text-sm font-semibold">{{ $bill->order_number }}</p>
                                    <p class="truncate text-xs text-ink-3">
                                        {{ $bill->created_at?->format('h:i A') }} · {{ strtoupper($bill->payment_mode->value) }}
                                        @if ($bill->customer_name) · {{ $bill->customer_name }} @endif
                                        @if ($bill->status->value === 'cancelled') · <span class="text-red-500">Cancelled</span> @endif
                                    </p>
                                </div>
                                <span class="text-sm font-semibold tabular-nums">{{ $money($bill->total_amount) }}</span>
                                <button wire:click="reprint({{ $bill->id }})" class="rounded-lg border border-line px-2.5 py-1.5 text-xs font-semibold hover:border-brand-400" title="Reprint bill"><x-icon name="printer" class="h-4 w-4" /></button>
                            </div>
                        @empty
                            <p class="py-12 text-center text-sm text-ink-3">No bills yet today.</p>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================== SETTLED / REPRINT PANEL --}}
    @if ($lastReceipt && $showReceipt)
        <div class="fixed inset-0 z-50 grid place-items-center p-4 print:hidden" x-on:keydown.escape.window="$wire.closeReceipt()">
            <div class="absolute inset-0 bg-black/50" wire:click="closeReceipt"></div>
            <div class="relative flex max-h-full w-full max-w-md flex-col overflow-hidden rounded-2xl bg-surface shadow-pop">
                <div class="flex items-center gap-3 border-b border-line px-5 py-4">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"><x-icon name="check" class="h-5 w-5" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-lg font-semibold">{{ $money($lastReceipt['total_amount']) }} {{ ! empty($lastReceipt['reprint']) ? '' : 'received' }}</p>
                        <p class="truncate text-xs text-ink-3">Bill {{ $lastReceipt['order_number'] }} · {{ strtoupper($lastReceipt['payment_mode']) }}
                            @if (bccomp((string) ($lastReceipt['change_amount'] ?? '0'), '0', 2) > 0) · Change {{ $money($lastReceipt['change_amount']) }} @endif
                        </p>
                    </div>
                    <button wire:click="closeReceipt" class="grid h-8 w-8 place-items-center rounded-lg hover:bg-surface-2"><x-icon name="x" class="h-4 w-4" /></button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto bg-surface-3 p-4">
                    <div class="rounded-md p-3 shadow-card" style="background:#fff">
                        @include('partials.receipt', ['r' => $lastReceipt, 'reprint' => ! empty($lastReceipt['reprint'])])
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 border-t border-line p-3">
                    <button onclick="bbPrint('receipt')" class="flex flex-col items-center gap-1 rounded-xl border border-line py-2.5 text-xs font-semibold hover:border-brand-400"><x-icon name="printer" class="h-5 w-5" />Print bill</button>
                    <button onclick="bbPrint('kot')" class="flex flex-col items-center gap-1 rounded-xl border border-line py-2.5 text-xs font-semibold hover:border-brand-400"><x-icon name="chef" class="h-5 w-5" />Print KOT</button>
                    <a href="{{ $this->whatsappLink }}" target="_blank" rel="noopener" class="flex flex-col items-center gap-1 rounded-xl border border-line py-2.5 text-xs font-semibold text-[#128C7E] hover:border-[#25D366] dark:text-[#25D366]"><x-icon name="whatsapp" class="h-5 w-5" />WhatsApp</a>
                </div>
                <div class="px-3 pb-3">
                    <button wire:click="closeReceipt" class="h-11 w-full rounded-xl bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700">New bill</button>
                </div>
            </div>
        </div>
    @endif

    {{-- print-only copies (hidden on screen) --}}
    @if ($lastReceipt)
        <div id="print-receipt" class="hidden">@include('partials.receipt', ['r' => $lastReceipt, 'reprint' => ! empty($lastReceipt['reprint'])])</div>
        <div id="print-kot" class="hidden">@include('partials.kot', ['r' => $lastReceipt])</div>
    @endif

    <script src="{{ asset('js/vendor/qrcode.js') }}"></script>
    @script
        <script>
            window.bbQrSvg = (text) => {
                if (typeof qrcode === 'undefined') return '';
                const qr = qrcode(0, 'M');
                qr.addData(text);
                qr.make();
                return qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
            };

            window.bbPrint = (copy) => {
                document.body.dataset.print = copy === 'kot' ? 'kot' : 'receipt';
                window.print();
            };
            window.addEventListener('afterprint', () => { delete document.body.dataset.print; });

            if (! window.bbPosKeys) {
                window.bbPosKeys = true;
                document.addEventListener('keydown', (event) => {
                    const map = { F2: 'shortcut-clear-cart', F6: 'shortcut-hold', F8: 'shortcut-cash', F9: 'shortcut-upi' };
                    if (event.key === 'F4') {
                        event.preventDefault();
                        document.getElementById('pos-search')?.focus();
                    } else if (map[event.key]) {
                        event.preventDefault();
                        Livewire.dispatch(map[event.key]);
                    }
                });
            }

            // Settle commits first, the DOM morphs in the receipt, then we print.
            $wire.on('trigger-print', (event) => {
                setTimeout(() => bbPrint(event?.copy ?? 'receipt'), 150);
            });
        </script>
    @endscript
</div>
