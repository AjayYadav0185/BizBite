{{-- =====================================================================
     BizBite — STAFF POS PORTAL (Livewire 3)
     Split-pane billing dashboard. All state is Livewire-driven; the only
     JS present is the keyboard-shortcut bridge and the `trigger-print`
     listener that opens the native thermal print dialog.
====================================================================== --}}
<div class="flex h-screen flex-col overflow-hidden bg-slate-950 text-slate-100" x-data>
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
            }
            @page { margin: 3mm; size: 80mm auto; }
        }
    </style>

    {{-- ---------------------------------------------------- TOP BAR --}}
    <header class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-5 py-3 print:hidden">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-500 font-black text-slate-950">B</span>
            <div>
                <p class="text-sm font-bold leading-tight">{{ auth()->user()?->store?->name ?? 'BizBite POS' }}</p>
                <p class="text-xs text-slate-400">Cashier: {{ auth()->user()?->name }}</p>
            </div>
        </div>

        <div class="hidden items-center gap-2 text-xs text-slate-300 md:flex">
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F2</span> Clear
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F4</span> Search
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F8</span> Cash
            <span class="rounded border border-slate-700 bg-slate-800 px-2 py-1 font-mono">F9</span> UPI
        </div>

        <a href="{{ route('logout') }}" class="text-xs font-semibold text-slate-400 hover:text-red-400">Logout</a>
    </header>

    {{-- ---------------------------------------------------- FLASH --}}
    <div class="px-5 pt-3 print:hidden">
        @if ($error)
            <p class="rounded-md bg-red-500/15 px-4 py-2 text-sm font-semibold text-red-300" role="alert">{{ $error }}</p>
        @endif
        @if ($success)
            <p class="rounded-md bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-300" role="status">{{ $success }}</p>
        @endif
    </div>

    {{-- ---------------------------------------------------- SPLIT PANE --}}
    <div class="flex min-h-0 flex-1 gap-5 p-5 print:hidden">

        {{-- LEFT PANE: MENU GRID --}}
        <section class="flex min-w-0 flex-1 flex-col gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <input
                    id="pos-search"
                    type="search"
                    placeholder="Search menu…  (F4)"
                    wire:model.live.debounce.300ms="search"
                    class="w-full max-w-xs rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-sm outline-none focus:border-emerald-500 sm:w-64"
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

            <div class="grid min-h-0 flex-1 auto-rows-min grid-cols-2 gap-3 overflow-y-auto pb-4 sm:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->menuItems as $item)
                    <button
                        wire:key="menu-{{ $item->id }}"
                        wire:click="addItem({{ $item->id }})"
                        class="group flex flex-col items-start rounded-xl border border-slate-800 bg-slate-900 p-4 text-left transition hover:border-emerald-500 hover:bg-slate-800 active:scale-[.97]"
                    >
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item->category?->name }}</span>
                        <span class="mt-1 text-sm font-bold leading-snug">{{ $item->name }}</span>
                        <span class="mt-2 text-base font-black text-emerald-400">₹{{ $item->price }}</span>
                    </button>
                @empty
                    <p class="col-span-full rounded-xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-400">
                        No menu items match. Adjust the search or category filter.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- RIGHT PANE: LIVE CART --}}
        <aside class="flex w-[22rem] flex-none flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Current Bill</h2>
                <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold text-emerald-400">{{ $this->cartCount }} items</span>
            </div>

            <ul class="min-h-0 flex-1 divide-y divide-slate-800 overflow-y-auto">
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
                <div class="mb-3 flex items-center justify-between text-lg">
                    <span class="font-bold text-slate-300">TOTAL</span>
                    <span class="font-black text-emerald-400">₹{{ $this->cartTotal }}</span>
                </div>

                <div class="mb-3 grid grid-cols-2 gap-2" role="group" aria-label="Payment mode">
                    <button wire:click="setPaymentMode('cash')"
                            class="rounded-lg py-2 text-sm font-black transition {{ $paymentMode === 'cash' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        CASH (F8)
                    </button>
                    <button wire:click="setPaymentMode('upi')"
                            class="rounded-lg py-2 text-sm font-black transition {{ $paymentMode === 'upi' ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                        UPI (F9)
                    </button>
                </div>

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

    {{-- ---------------------------------------------------- THERMAL RECEIPT --}}
    {{-- Hidden on screen; becomes the ONLY visible content when printing. --}}
    <div id="thermal-receipt" class="hidden" aria-hidden="true">
        @if ($lastReceipt)
            <div class="text-center">
                @if (filled($lastReceipt['store']['print_header']))
                    <p class="text-[13px] font-bold uppercase">{{ $lastReceipt['store']['print_header'] }}</p>
                @endif
                <p class="text-[15px] font-bold uppercase">{{ $lastReceipt['store']['name'] }}</p>
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
            <div class="flex justify-between font-bold">
                <span>TOTAL</span>
                <span>Rs. {{ $lastReceipt['total_amount'] }}</span>
            </div>
            <div class="flex justify-between uppercase">
                <span>Paid via</span>
                <span>{{ $lastReceipt['payment_mode'] }}</span>
            </div>
            <p class="my-1">--------------------------------</p>

            <div class="text-center">
                @if (filled($lastReceipt['store']['print_footer']))
                    <p>{{ $lastReceipt['store']['print_footer'] }}</p>
                @endif
                <p class="mt-1">Powered by BizBite</p>
            </div>
        @endif
    </div>

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
