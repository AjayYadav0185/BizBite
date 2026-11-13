@php
    // Kitchen board palette — mirrors the dark POS theme.
    $statusStyles = [
        'pending' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30',
        'preparing' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30',
        'ready' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
        'completed' => 'bg-slate-500/15 text-ink-2 border-slate-500/30',
        'cancelled' => 'bg-red-500/15 text-red-700 dark:text-red-300 border-red-500/30',
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
<div class="flex min-h-screen flex-col bg-bg text-ink" wire:poll.10s>

    @include('partials.pos-nav', ['active' => 'orders'])

    {{-- ---------------------------------------------------- FLASH --}}
    @if ($error)
        <div class="px-4 pt-3 sm:px-5">
            <p class="rounded-md bg-red-500/15 px-4 py-2 text-sm font-semibold text-red-700 dark:text-red-300" role="alert">{{ $error }}</p>
        </div>
    @endif

    {{-- ---------------------------------------------------- STATUS TILES --}}
    <div class="grid grid-cols-2 gap-3 px-4 pt-4 sm:grid-cols-3 sm:px-5 lg:grid-cols-5">
        @foreach (\App\Models\Enums\OrderStatus::cases() as $status)
            <div class="rounded-xl border border-line bg-surface px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-3">{{ $status->label() }}</p>
                <p @class(['mt-1 text-2xl font-bold tabular-nums',
                    'text-amber-600 dark:text-amber-400' => $status->value === 'pending',
                    'text-sky-600 dark:text-sky-400' => $status->value === 'preparing',
                    'text-brand-ink' => $status->value === 'ready',
                    'text-ink-2' => $status->value === 'completed',
                    'text-red-600 dark:text-red-400' => $status->value === 'cancelled'])>
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
                            $typeFilter === $value ? 'bg-brand-600 text-white' : 'bg-surface-2 text-ink-2 hover:bg-surface-3'])>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <span class="mx-1 hidden h-5 w-px bg-surface-2 sm:block"></span>

        <div class="flex gap-2" role="tablist" aria-label="Status scope">
            <button wire:click="setStatusFilter('open')"
                    @class(['rounded-full px-3 py-1.5 text-xs font-bold transition',
                        $statusFilter === 'open' ? 'bg-ink text-bg' : 'bg-surface-2 text-ink-2 hover:bg-surface-3'])>
                Live only
            </button>
            <button wire:click="setStatusFilter('all')"
                    @class(['rounded-full px-3 py-1.5 text-xs font-bold transition',
                        $statusFilter === 'all' ? 'bg-ink text-bg' : 'bg-surface-2 text-ink-2 hover:bg-surface-3'])>
                All today
            </button>
        </div>
    </div>

    {{-- ---------------------------------------------------- BOARD --}}
    <main class="grid flex-1 auto-rows-min grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3">
        @forelse ($this->boardOrders as $order)
            <article wire:key="order-{{ $order->id }}"
                     class="flex flex-col rounded-2xl border border-line bg-surface p-4">

                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-mono text-sm font-bold">{{ $order->order_number }}</p>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ $typeLabels[$order->order_type?->value] ?? 'Order' }}
                            · {{ $order->user?->name ?? '—' }}
                            · {{ $order->created_at?->format('h:i A') }}
                        </p>
                    </div>
                    <span @class(['shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider', $statusStyles[$order->status->value] ?? 'bg-surface-2 text-ink-2'])>
                        {{ $order->status->label() }}
                    </span>
                </div>

                <ul class="mt-3 flex-1 space-y-1 border-y border-dashed border-line py-3">
                    @foreach ($order->items as $line)
                        <li class="flex items-baseline justify-between gap-2 text-sm">
                            <span class="min-w-0 truncate">
                                <span class="font-bold text-brand-ink">{{ $line->quantity }}×</span>
                                {{ $line->food_item_name }}
                            </span>
                            <span class="shrink-0 tabular-nums text-ink-3">₹{{ $line->subtotal }}</span>
                        </li>
                    @endforeach
                </ul>

                @if (filled($order->notes))
                    <p class="mt-2 rounded-lg bg-amber-500/10 px-2.5 py-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                        📝 {{ $order->notes }}
                    </p>
                @endif

                <div class="mt-2 flex flex-wrap gap-1.5 text-[11px]">
                    @if (filled($order->table_number))
                        <span class="rounded-full bg-sky-500/15 px-2 py-0.5 font-bold text-sky-700 dark:text-sky-300">Table {{ $order->table_number }}</span>
                    @endif
                    <span class="rounded-full bg-surface-2 px-2 py-0.5 font-bold uppercase text-ink-2">{{ $order->payment_mode->value }}</span>
                    @if (filled($order->campaign_code))
                        <span class="rounded-full bg-violet-500/15 px-2 py-0.5 font-bold text-violet-700 dark:text-violet-300">{{ $order->campaign_code }} −₹{{ $order->campaign_discount }}</span>
                    @endif
                    @if (bccomp((string) ($order->refunded_amount ?? '0.00'), '0', 2) > 0)
                        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 font-bold text-amber-700 dark:text-amber-300">Refunded ₹{{ $order->refunded_amount }}</span>
                    @endif
                    @if ($order->order_type?->value === 'delivery')
                        <span class="rounded-full bg-teal-500/15 px-2 py-0.5 font-bold text-teal-700 dark:text-teal-300">🚚 {{ $order->delivery_status }}{{ filled($order->delivery_agent) ? ' · '.$order->delivery_agent : '' }}</span>
                    @endif
                </div>
                @if ($order->order_type?->value === 'delivery' && filled($order->delivery_address))
                    <p class="mt-1.5 text-xs text-ink-3">📍 {{ $order->delivery_address }}</p>
                @endif

                <div class="mt-2 flex items-center justify-between text-xs text-ink-3">
                    <span>{{ $order->items->sum('quantity') }} items · {{ (int) $order->created_at?->diffInMinutes(now()) }}m ago</span>
                    <span class="font-bold text-ink">₹{{ $order->total_amount }}</span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($order->status->next() !== null)
                        <button wire:click="advance({{ $order->id }})"
                                class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">
                            → {{ $order->status->next()->label() }}
                        </button>
                    @endif

                    @if (in_array($order->status->value, ['pending', 'preparing'], strict: true))
                        <button wire:click="setStatus({{ $order->id }}, 'completed')"
                                class="rounded-lg bg-surface-2 px-3 py-1.5 text-xs font-bold text-ink transition hover:bg-surface-3">
                            Complete now
                        </button>
                    @endif

                    @if ($order->status->value !== 'cancelled')
                        <button wire:click="cancelOrder({{ $order->id }})"
                                wire:confirm="Cancel bill {{ $order->order_number }}? Stock will be returned to the menu."
                                class="ml-auto rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-bold text-red-700 dark:text-red-300 transition hover:bg-red-500/10">
                            Cancel
                        </button>
                    @endif
                </div>

                <details class="mt-3 rounded-xl border border-line">
                    <summary class="cursor-pointer px-3 py-2 text-xs font-bold text-ink-3 hover:text-ink">Refund / delivery actions</summary>
                    <div class="p-3 pt-1">
                        <livewire:pos.queue-actions :orderId="$order->id" :key="'qa-'.$order->id" />
                    </div>
                </details>
            </article>
        @empty
            <p class="col-span-full rounded-2xl border border-dashed border-line-strong p-12 text-center text-sm text-ink-3">
                @if ($statusFilter === 'open')
                    No live orders right now. New bills appear here instantly.
                @else
                    No bills yet today.
                @endif
            </p>
        @endforelse
    </main>
</div>

