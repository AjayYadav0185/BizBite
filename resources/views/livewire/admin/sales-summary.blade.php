<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Sales Summary</h1>
            <p class="text-sm text-slate-500">Today · {{ now()->format('l, d M Y') }} · Store #{{ auth()->user()?->store_id }}</p>
        </div>
        <button wire:click="$refresh"
                class="rounded-xl border border-card-border bg-white px-4 py-2 text-sm font-bold text-slate-600 shadow-sm hover:bg-surface-subtle">
            ↻ Refresh
        </button>
    </div>

    {{-- ---------------------------------------------------- KPI TILES --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-card-border bg-white p-5 shadow-card">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Today's Revenue</p>
            <p class="mt-2 text-3xl font-black tabular-nums text-brand-600">₹{{ number_format((float) $this->todaysStats['revenue'], 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">Avg bill ₹{{ $this->todaysStats['average_bill'] }}</p>
        </div>
        <div class="rounded-2xl border border-card-border bg-white p-5 shadow-card">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Total Bills</p>
            <p class="mt-2 text-3xl font-black tabular-nums text-brand-600">{{ $this->todaysStats['bills'] }}</p>
            <p class="mt-1 text-xs text-slate-400">Completed settlements today</p>
        </div>
        <div class="rounded-2xl border border-card-border bg-white p-5 shadow-card sm:col-span-2 lg:col-span-1">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Payment Mix</p>
            <div class="mt-3 space-y-2">
                @forelse ($this->todaysStats['modes'] as $mode)
                    <div>
                        <div class="flex justify-between text-xs font-bold text-slate-600">
                            <span class="uppercase">{{ $mode['label'] }}</span>
                            <span class="tabular-nums">{{ $mode['percentage'] }}% · ₹{{ number_format((float) $mode['revenue'], 2) }}</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-surface-muted">
                            <div class="h-1.5 rounded-full {{ $mode['label'] === 'cash' ? 'bg-brand-500' : ($mode['label'] === 'upi' ? 'bg-teal-500' : 'bg-warn-500') }}"
                                 style="width: {{ $mode['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No sales yet today.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ---------------------------------------------------- RECENT BILLS --}}
    <section class="overflow-hidden rounded-2xl border border-card-border bg-white shadow-card">
        <header class="flex items-center justify-between border-b border-surface-subtle bg-surface-muted px-4 py-3 sm:px-5">
            <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Recent Bills</h2>
            <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-black uppercase text-brand-700">Live feed</span>
        </header>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-2 font-bold sm:px-5">Bill No.</th>
                        <th class="px-4 py-2 font-bold sm:px-5">Cashier</th>
                        <th class="px-4 py-2 font-bold sm:px-5">Payment</th>
                        <th class="px-4 py-2 font-bold sm:px-5">Time</th>
                        <th class="px-4 py-2 text-right font-bold sm:px-5">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-subtle">
                    @forelse ($this->recentBills as $bill)
                        <tr wire:key="bill-{{ $bill->id }}" class="hover:bg-brand-50/60">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700 sm:px-5">{{ $bill->order_number }}</td>
                            <td class="px-4 py-3 text-slate-600 sm:px-5">{{ $bill->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 sm:px-5">
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-black uppercase text-brand-700">{{ $bill->payment_mode->value }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-500 sm:px-5">{{ $bill->created_at?->format('h:i A') }}</td>
                            <td class="px-4 py-3 text-right font-black tabular-nums text-brand-600 sm:px-5">₹{{ $bill->total_amount }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 sm:px-5">No bills yet — the POS feed will appear here live.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
