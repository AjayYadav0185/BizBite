{{-- =====================================================================
     BizBite — STAFF POS PORTAL (Livewire 3)
     Split-pane billing dashboard. All state is Livewire-driven; the only
     JS present is the keyboard-shortcut bridge and the `trigger-print`
     listener that opens the native thermal print dialog.
====================================================================== --}}
<div class="flex min-h-screen flex-col overflow-hidden bg-slate-950 pb-14 text-slate-100 lg:pb-0" x-data>
    <style>
        /* ------------------------------------------------------------
           THERMAL PRINT LAYOUT — only #thermal-receipt reaches paper.
           Everything else (the whole app shell) is stripped.
           80mm thermal roll: 72mm printable width, monospace receipt.
        ------------------------------------------------------------ */
        @media print {
            body * { visibility: hidden !important; }
            #thermal-receipt, #thermal-receipt * { visibility: visible !important; }
            #thermal-receipt {
                /* #thermal-receipt is display:none on screen (Tailwind 'hidden');
                   visibility alone cannot override display:none, so without this
                   the 80mm roll printed BLANK. Force it visible for printing. */
                display: block !important;
                position: absolute !important;
                inset: 0 !important;
                width: 72mm !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000 !important;
                background: #fff !important;
                font-family: 'Courier New', monospace !important;
                font-size: 12px !important;
                line-height: 1.35 !important;
                box-shadow: none !important;
                border: 0 !important;
            }
            @page { margin: 3mm; size: 80mm auto; }
        }
    </style>

    {{-- ---------------------------------------------------- TOP BAR --}}
    <header class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-4 py-3 print:hidden sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-500 font-black text-slate-950">B</span>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold leading-tight">{{ auth()->user()?->store?->name ?? 'BizBite POS' }}</p>
                <p class="truncate text-xs text-slate-400">Cashier: {{ auth()->user()?->name }}</p>
            </div>
        </div>

        <div class="hidden items-center gap-2 text-xs text-slate-300 md:flex">
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F2</span> Clear
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F4</span> Search
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F8</span> Cash
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F9</span> UPI
        </div>

        <div class="flex items-center gap-3 print:hidden">
            <a href="{{ route('pos.orders') }}"
               class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-emerald-500 hover:text-emerald-400">
                Order Queue →
            </a>
            <a href="{{ route('pos.shift') }}"
               class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-emerald-500 hover:text-emerald-400">
                Shift
            </a>
            @if (auth()->user()?->isAdmin())
                {{-- Owners can hop back to the Admin console from the POS —
                     without this an admin who switches to POS mode is stuck
                     with no UI way back (only Order Queue had the link). --}}
                <a href="{{ route('admin.dashboard') }}"
                   class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-emerald-500 hover:text-emerald-400">
                    Admin
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-slate-400 hover:text-red-400">Logout</button>
            </form>
        </div>
    </header>

    {{-- ---------------------------------------------------- FLASH --}}
    <div class="px-4 pt-3 print:hidden sm:px-5">
        @if ($error)
            <p class="rounded-md bg-red-500/15 px-4 py-2 text-sm font-semibold text-red-300" role="alert">{{ $error }}</p>
        @endif
        @if ($success)
            <p class="rounded-md bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-300" role="status">{{ $success }}</p>
        @endif
    </div>

    {{-- ---------------------------------------------------- SPLIT PANE --}}
    <div class="flex flex-col gap-4 p-4 print:hidden sm:p-5 lg:min-h-0 lg:flex-1 lg:flex-row lg:items-stretch lg:gap-5">


        {{-- LEFT PANE: MENU GRID --}}
        <section class="flex min-w-0 flex-col gap-4 lg:flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <input
                    id="pos-search"
                    type="search"
                    placeholder="Search menu…  (F4)"
                    wire:model.live.debounce.300ms="search"
                    class="w-full max-w-xs rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-white outline-none focus:border-emerald-500 sm:w-64"
                />

                <div class="flex flex-wrap gap-2" role="tablist">
                    <button wire:click="$set('activeCategoryId', null)"
                            class="rounded-full px-3 py-1.5 text-xs font-bold transition {{ $activeCategoryId === null ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        All
                    </button>
                    @foreach ($this->categories as $category)
                        <button wire:click="$set('activeCategoryId', {{ $category->id }})"
                                wire:key="cat-{{ $category->id }}"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition {{ $activeCategoryId === $category->id ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid auto-rows-min grid-cols-2 gap-3 overflow-y-auto pb-4 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->menuItems as $item)
                    <button
                        wire:key="menu-{{ $item->id }}"
                        @if ($item->isOutOfStock()) disabled @else wire:click="addItem({{ $item->id }})" @endif
                        @class([
                            'group flex flex-col items-start rounded-xl border p-4 text-left transition',
                            $item->isOutOfStock()
                                ? 'cursor-not-allowed border-slate-800 bg-slate-900/50 opacity-60'
                                : 'border-slate-800 bg-slate-900 hover:border-emerald-500 hover:bg-slate-800 active:scale-[.97]',
                        ])
                    >
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item->category?->name }}</span>
                        <span class="mt-1 text-sm font-bold leading-snug">{{ $item->name }}</span>
                        <span @class(['mt-2 text-base font-black', $item->isOutOfStock() ? 'text-slate-500 line-through' : 'text-emerald-400'])>₹{{ $item->price }}</span>
                        @if ($item->isOutOfStock())
                            <span class="mt-1.5 rounded-full bg-red-500/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-red-300">Sold out</span>
                        @elseif ($item->isLowStock())
                            <span class="mt-1.5 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-300">Only {{ $item->stock_quantity }} left</span>
                        @endif
                    </button>
                @empty
                    <p class="col-span-full rounded-xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-400">
                        No menu items match. Adjust the search or category filter.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- RIGHT PANE: LIVE CART --}}
        <aside id="pos-cart" class="mt-4 flex w-full max-h-[34rem] scroll-mt-28 flex-none flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 lg:mt-0 lg:w-[22rem] lg:max-h-none">
            <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Current Bill</h2>
                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold text-emerald-400">{{ $this->cartCount }} items</span>
            </div>

            <ul class="max-h-72 min-h-0 flex-1 divide-y divide-slate-800 overflow-y-auto lg:max-h-none">
                @forelse ($cart as $line)
                    <li wire:key="cart-{{ $line['id'] }}" class="flex items-center gap-2 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold">{{ $line['name'] }}</p>
                            <p class="text-xs text-slate-400">₹{{ $line['price'] }} × {{ $line['quantity'] }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="decrementQuantity({{ $line['id'] }})"
                                    class="h-7 w-7 rounded-md bg-slate-800 font-black text-slate-200 hover:bg-slate-700">−</button>
                            <span class="w-8 text-center text-sm font-black">{{ $line['quantity'] }}</span>
                            <button wire:click="incrementQuantity({{ $line['id'] }})"
                                    class="h-7 w-7 rounded-md bg-slate-800 font-black text-slate-200 hover:bg-slate-700">+</button>
                        </div>
                        <p class="w-16 text-right text-sm font-black text-emerald-400">
                            ₹{{ number_format((float) bcmul($line['price'], (string) $line['quantity'], 2), 2) }}
                        </p>
                        <button wire:click="removeItem({{ $line['id'] }})"
                                class="text-slate-500 hover:text-red-400" title="Remove line">✕</button>
                    </li>
                @empty
                    <li class="grid h-full place-items-center px-6 text-center text-sm text-slate-500">
                        Tap menu items or press F4 to search.<br/>F8 = Cash · F9 = UPI
                    </li>
                @endforelse
            </ul>

            <div class="border-t border-slate-800 px-4 py-4">
                {{-- Bill-level discount + free-text note (MVP scope §6) --}}
                <div class="mb-3 grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Discount ₹</span>
                        <input type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00"
                               wire:model.live.debounce.400ms="discountInput"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm font-bold text-white outline-none focus:border-emerald-500" />
                    </label>
                    <label class="block">
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Note</span>
                        <input type="text" maxlength="200" placeholder="e.g. no onion"
                               wire:model.live.debounce.400ms="notesInput"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-500" />
                    </label>
                </div>

                {{-- Order type + table + campaign + tendered (next-phase completion) --}}
                <div class="mb-3 grid grid-cols-4 gap-1.5" role="group" aria-label="Order type">
                    @foreach (['dine_in' => 'Dine-in', 'takeaway' => 'Takeaway', 'parcel' => 'Parcel', 'delivery' => 'Delivery'] as $value => $label)
                        <button wire:click="setOrderType('{{ $value }}')"
                                class="rounded-lg py-1.5 text-[11px] font-black uppercase transition {{ $orderType === $value ? 'bg-sky-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="mb-3 grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Table (dine-in)</span>
                        <input type="text" maxlength="20" placeholder="T1"
                               wire:model.live.debounce.400ms="tableNumberInput"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-500" />
                    </label>
                    <label class="block">
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Campaign code</span>
                        <input type="text" maxlength="40" placeholder="DIWALI10"
                               wire:model.live.debounce.400ms="campaignInput"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm uppercase text-white outline-none focus:border-emerald-500" />
                    </label>
                </div>

                @if ($orderType === 'delivery')
                    <div class="mb-3 grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Delivery address</span>
                            <input type="text" maxlength="255" placeholder="Flat / street / landmark"
                                   wire:model.live.debounce.400ms="deliveryAddressInput"
                                   class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-500" />
                        </label>
                        <label class="block">
                            <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Agent</span>
                            <input type="text" maxlength="80" placeholder="Rider name"
                                   wire:model.live.debounce.400ms="deliveryAgentInput"
                                   class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-500" />
                        </label>
                    </div>
                @endif

                <div class="mb-3 grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Tendered ₹</span>
                        <input type="number" min="0" step="0.01" placeholder="Cash received"
                               wire:model.live.debounce.400ms="tenderedInput"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-500" />
                    </label>
                    <label class="block">
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">UPI ref</span>
                        <input type="text" maxlength="60" placeholder="UTR (UPI/split)"
                               wire:model.live.debounce.400ms="upiRefInput"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-500" />
                    </label>
                </div>
                @if (bccomp($this->changeDue, '0', 2) > 0)
                    <p class="mb-3 rounded-lg bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-300">Change due: ₹{{ $this->changeDue }}</p>
                @endif

                <div class="mb-2 space-y-1 text-xs text-slate-400">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="tabular-nums">₹{{ $this->cartTotal }}</span>
                    </div>
                    @if (bccomp($this->cartDiscount, '0', 2) > 0)
                        <div class="flex justify-between text-amber-400">
                            <span>Discount</span>
                            <span class="tabular-nums">−₹{{ $this->cartDiscount }}</span>
                        </div>
                    @endif
                </div>

                <div class="mb-3 flex items-center justify-between text-lg">
                    <span class="font-bold text-slate-300">TOTAL</span>
                    <span class="font-black text-emerald-400">₹{{ $this->cartGrandTotal }}</span>
                </div>

                <div class="mb-3 grid grid-cols-3 gap-2" role="group" aria-label="Payment mode">
                    <button wire:click="setPaymentMode('cash')"
                            class="rounded-lg py-2 text-xs font-black transition {{ $paymentMode === 'cash' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        CASH (F8)
                    </button>
                    <button wire:click="setPaymentMode('upi')"
                            class="rounded-lg py-2 text-xs font-black transition {{ $paymentMode === 'upi' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        UPI (F9)
                    </button>
                    <button wire:click="setPaymentMode('card')"
                            class="rounded-lg py-2 text-xs font-black transition {{ $paymentMode === 'card' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        CARD
                    </button>
                    <button wire:click="setPaymentMode('credit')"
                            class="rounded-lg py-2 text-xs font-black transition {{ $paymentMode === 'credit' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        CREDIT
                    </button>
                    <button wire:click="setPaymentMode('split')"
                            class="col-span-2 rounded-lg py-2 text-xs font-black transition {{ $paymentMode === 'split' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        SPLIT (₹{{ $this->splitTotal }})
                    </button>
                </div>

                @if ($paymentMode === 'split')
                    <div class="mb-3 rounded-lg border border-dashed border-slate-700 p-2.5">
                        <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">
                            Split legs must total ₹{{ $this->cartGrandTotal }}
                        </p>
                        <div class="mt-2 flex gap-1.5">
                            <select id="split-mode" class="flex-1 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-xs text-white">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="credit">Credit</option>
                            </select>
                            <input id="split-amount" type="number" min="0.01" step="0.01" placeholder="₹"
                                   class="w-24 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-xs text-white" />
                            <button onclick="const m = document.getElementById('split-mode').value; const a = document.getElementById('split-amount').value; if (a) { @this.call('addSplitLeg', m, a); document.getElementById('split-amount').value = ''; }"
                                    class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-bold text-white">Add</button>
                        </div>
                        @foreach ($splitLegs as $i => $leg)
                            <div class="mt-1.5 flex items-center justify-between text-xs text-slate-300">
                                <span class="uppercase">{{ $leg['mode'] }} · ₹{{ $leg['amount'] }}</span>
                                <button wire:click="removeSplitLeg({{ $i }})" class="text-slate-500 hover:text-red-400">✕</button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <button
                    wire:click="checkout('{{ $paymentMode }}')"
                    wire:loading.attr="disabled"
                    @class(['w-full rounded-xl bg-emerald-500 py-3 text-sm font-black uppercase tracking-widest text-slate-950 transition hover:bg-emerald-400 disabled:opacity-50', 'pointer-events-none opacity-50' => $cart === []])
                >
                    🖨 Print &amp; Settle
                </button>
            </div>
        </aside>
    </div>

    {{-- MOBILE QUICK-BILL BAR — anchors to the cart panel stacked below on
         phones/tablets so cashiers can always see the running total. --}}
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-800 bg-slate-900/95 px-4 py-3 backdrop-blur-sm print:hidden lg:hidden">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold text-emerald-400">{{ $this->cartCount }} items</span>
                <span class="text-sm font-black text-emerald-400">₹{{ $this->cartGrandTotal }}</span>
            </div>
            <a href="#pos-cart" class="rounded-lg bg-emerald-500 px-4 py-2 text-xs font-black uppercase tracking-wider text-slate-950">View Bill ↓</a>
        </div>
    </div>

    {{-- ---------------------------------------------------- THERMAL RECEIPT --}}
    {{-- Shown on screen as a bill preview after checkout; in print became the
         ONLY visible content (the @media print rules strip the whole app). --}}
    @if ($lastReceipt)
        <div id="thermal-receipt"
             class="mx-auto mb-5 w-full max-w-sm rounded-xl border border-slate-800 bg-white px-4 py-4 text-black"
             aria-label="Bill preview">

            <div class="mb-2 flex items-center justify-between border-b border-dashed border-slate-300 pb-2 print:hidden">
                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Bill {{ $lastReceipt['order_number'] }}</span>
                <button type="button" onclick="window.print()"
                        class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-slate-950 hover:bg-emerald-400">
                    🖨 Print Bill
                </button>
            </div>

            <div class="mx-auto w-[72mm] max-w-full font-mono text-[13px] leading-[1.4]" id="thermal-paper">
                <div class="text-center">
                    @if (filled($lastReceipt['store']['print_header']))
                        <p class="text-[12px] font-bold uppercase">{{ $lastReceipt['store']['print_header'] }}</p>
                    @endif
                    <p class="text-[14px] font-bold uppercase">{{ $lastReceipt['store']['name'] }}</p>
                    @if (filled($lastReceipt['store']['address']))
                        <p>{{ $lastReceipt['store']['address'] }}</p>
                    @endif
                    @if (filled($lastReceipt['store']['phone']))
                        <p>Ph: {{ $lastReceipt['store']['phone'] }}</p>
                    @endif
                </div>

                <p class="my-1">--------------------------------</p>
                <div class="flex justify-between">
                    <span>Bill: {{ $lastReceipt['order_number'] }}</span>
                    <span>{{ $lastReceipt['placed_at'] }}</span>
                </div>
                @if (filled($lastReceipt['cashier']))
                    <div class="flex justify-between">
                        <span>Cashier: {{ $lastReceipt['cashier'] }}</span>
                    </div>
                @endif
                <p class="my-1">--------------------------------</p>

                @foreach ($lastReceipt['items'] as $item)
                    <div class="flex justify-between">
                        <span class="truncate pr-1">{{ $item['quantity'] }} x {{ $item['food_item_name'] }}</span>
                        <span>{{ $item['subtotal'] }}</span>
                    </div>
                @endforeach

                <p class="my-1">--------------------------------</p>
                @if (bccomp((string) ($lastReceipt['discount_amount'] ?? '0.00'), '0', 2) > 0)
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>{{ $lastReceipt['subtotal'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Discount</span>
                        <span>-{{ $lastReceipt['discount_amount'] }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-bold">
                    <span>TOTAL</span>
                    <span>Rs. {{ $lastReceipt['total_amount'] }}</span>
                </div>
                <div class="flex justify-between uppercase">
                    <span>Paid via</span>
                    <span>{{ $lastReceipt['payment_mode'] }}</span>
                </div>
                @if (filled($lastReceipt['notes'] ?? null))
                    <p class="mt-1">Note: {{ $lastReceipt['notes'] }}</p>
                @endif
                <p class="my-1">--------------------------------</p>

                <div class="text-center">
                    @if (filled($lastReceipt['store']['print_footer']))
                        <p>{{ $lastReceipt['store']['print_footer'] }}</p>
                    @endif
                    <p class="mt-1">Powered by BizBite</p>
                </div>
            </div>
        </div>
    @endif

    {{-- ---------------------------------------------------- EVENT BRIDGE --}}
    {{-- Keyboard shortcuts -> Livewire events; `trigger-print` -> dialog. --}}
    @script
        <script>
            document.addEventListener('keydown', (event) => {
                switch (event.key) {
                    case 'F2':
                        event.preventDefault();
                        $wire.dispatch('shortcut-clear-cart');
                        break;
                    case 'F4':
                        event.preventDefault();
                        document.getElementById('pos-search')?.focus();
                        break;
                    case 'F8':
                        event.preventDefault();
                        $wire.dispatch('shortcut-cash');
                        break;
                    case 'F9':
                        event.preventDefault();
                        $wire.dispatch('shortcut-upi');
                        break;
                }
            });

            // The component commits the order first, updates the DOM with the
            // receipt data, then dispatches this browser event; a short delay
            // guarantees morphing is complete before the dialog opens.
            $wire.on('trigger-print', () => {
                setTimeout(() => window.print(), 120);
            });
        </script>
    @endscript
</div>
