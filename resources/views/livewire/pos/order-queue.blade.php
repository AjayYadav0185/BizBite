@php
    // Kitchen board palette — mirrors the dark POS theme.
    $statusStyles = [
        'pending' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'preparing' => 'bg-sky-500/15 text-sky-300 border-sky-500/30',
        'ready' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        'completed' => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
        'cancelled' => 'bg-red-500/15 text-red-300 border-red-500/30',
    ];

    $typeLabels = [
        'dine_in' => 'Dine-in',
        'takeaway' => 'Takeaway',
        'parcel' => 'Parcel',
        'delivery' => 'Delivery',
    ];
@endphp

{{-- =====================================================================
     BizBite — ORDER QUEUE (kitchen / counter board)
     Priority feature §5: order status updates. Live-polls so a kitchen
     tablet stays in sync with the billing counter without a refresh.
====================================================================== --}}
<div class="flex min-h-screen flex-col bg-slate-950 text-slate-100" wire:poll.10s>

    {{-- ---------------------------------------------------- TOP BAR --}}
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-900 px-4 py-3 sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-500 font-black text-slate-950">B</span>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold leading-tight">{{ auth()->user()?->store?->name ?? 'BizBite POS' }}</p>
                <p class="truncate text-xs text-slate-400">Order Queue · {{ now()->format('d M Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('pos.billing') }}"
               class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-slate-950 transition hover:bg-emerald-400">
                ← Billing
            </a>
            @if (auth()->user()?->isAdmin())
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
    @if ($error)
        <div class="px-4 pt-3 sm:px-5">
            <p class="rounded-md bg-red-500/15 px-4 py-2 text-sm font-semibold text-red-300" role="alert">{{ $error }}</p>
        </div>
    @endif

    {{-- ---------------------------------------------------- STATUS TILES --}}
    <div class="grid grid-cols-2 gap-3 px-4 pt-4 sm:grid-cols-3 sm:px-5 lg:grid-cols-5">
        @foreach (\App\Models\Enums\OrderStatus::cases() as $status)
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">{{ $status->label() }}</p>
                <p @class(['mt-1 text-2xl font-black tabular-nums',
                    'text-amber-400' => $status->value === 'pending',
                    'text-sky-400' => $status->value === 'preparing',
                    'text-emerald-400' => $status->value === 'ready',
                    'text-slate-300' => $status->value === 'completed',
                    'text-red-400' => $status->value === 'cancelled'])>
                    {{ $this->statusCounts[$status->value] ?? 0 }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- ---------------------------------------------------- FILTERS --}}
    <div class="flex flex-wrap items-center gap-2 px-4 pt-4 sm:px-5">
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Order type">
            @foreach (['all' => 'All types', 'dine_in' => 'Dine-in', 'takeaway' => 'Takeaway', 'parcel' => 'Parcel', 'delivery' => 'Delivery'] as $value => $label)
                <button wire:click="setTypeFilter('{{ $value }}')"
                        @class(['rounded-full px-3 py-1.5 text-xs font-bold transition',
                            $typeFilter === $value ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <span class="mx-1 hidden h-5 w-px bg-slate-800 sm:block"></span>

        <div class="flex gap-2" role="tablist" aria-label="Status scope">
            <button wire:click="setStatusFilter('open')"
                    @class(['rounded-full px-3 py-1.5 text-xs font-bold transition',
                        $statusFilter === 'open' ? 'bg-slate-200 text-slate-900' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'])>
                Live only
            </button>
            <button wire:click="setStatusFilter('all')"
                    @class(['rounded-full px-3 py-1.5 text-xs font-bold transition',
                        $statusFilter === 'all' ? 'bg-slate-200 text-slate-900' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'])>
                All today
            </button>
        </div>
    </div>

    {{-- ---------------------------------------------------- BOARD --}}
    <main class="grid flex-1 auto-rows-min grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3">
        @forelse ($this->boardOrders as $order)
            <article wire:key="order-{{ $order->id }}"
                     class="flex flex-col rounded-2xl border border-slate-800 bg-slate-900 p-4">

                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-mono text-sm font-black">{{ $order->order_number }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ $typeLabels[$order->order_type?->value] ?? 'Order' }}
                            · {{ $order->user?->name ?? '—' }}
                            · {{ $order->created_at?->format('h:i A') }}
                        </p>
                    </div>
                    <span @class(['shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-black uppercase tracking-wider', $statusStyles[$order->status->value] ?? 'bg-slate-800 text-slate-300'])>
                        {{ $order->status->label() }}
                    </span>
                </div>

                <ul class="mt-3 flex-1 space-y-1 border-y border-dashed border-slate-800 py-3">
                    @foreach ($order->items as $line)
                        <li class="flex items-baseline justify-between gap-2 text-sm">
                            <span class="min-w-0 truncate">
                                <span class="font-black text-emerald-400">{{ $line->quantity }}×</span>
                                {{ $line->food_item_name }}
                            </span>
                            <span class="shrink-0 tabular-nums text-slate-400">₹{{ $line->subtotal }}</span>
                        </li>
                    @endforeach
                </ul>

                @if (filled($order->notes))
                    <p class="mt-2 rounded-lg bg-amber-500/10 px-2.5 py-1.5 text-xs font-semibold text-amber-300">
                        📝 {{ $order->notes }}
                    </p>
                @endif

                <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ $order->items->sum('quantity') }} items · {{ (int) $order->created_at?->diffInMinutes(now()) }}m ago</span>
                    <span class="font-black text-slate-200">₹{{ $order->total_amount }}</span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($order->status->next() !== null)
                        <button wire:click="advance({{ $order->id }})"
                                class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-slate-950 transition hover:bg-emerald-400">
                            → {{ $order->status->next()->label() }}
                        </button>
                    @endif

                    @if (in_array($order->status->value, ['pending', 'preparing'], strict: true))
                        <button wire:click="setStatus({{ $order->id }}, 'completed')"
                                class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:bg-slate-700">
                            Complete now
                        </button>
                    @endif

                    @if ($order->status->value !== 'cancelled')
                        <button wire:click="cancelOrder({{ $order->id }})"
                                wire:confirm="Cancel bill {{ $order->order_number }}? Stock will be returned to the menu."
                                class="ml-auto rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-bold text-red-300 transition hover:bg-red-500/10">
                            Cancel
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <p class="col-span-full rounded-2xl border border-dashed border-slate-700 p-12 text-center text-sm text-slate-400">
                @if ($statusFilter === 'open')
                    No live orders right now. New bills appear here instantly.
                @else
                    No bills yet today.
                @endif
            </p>
        @endforelse
    </main>
</div>

