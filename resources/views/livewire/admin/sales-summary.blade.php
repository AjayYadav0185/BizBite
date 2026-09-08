<div class="space-y-6">
    <div class="flex items-end justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Sales Summary</h1>
            <p class="text-sm text-slate-500">Today · {{ now()->format('l, d M Y') }} · Store #{{ auth()->user()?->store_id }}</p>
        </div>
        <button wire:click="$refresh" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">
            ↻ Refresh
        </button>
    </div>

    {{-- ---------------------------------------------------- KPI TILES --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Today's Revenue</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">₹{{ number_format((float) $this->todaysStats['revenue'], 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">Avg bill ₹{{ $this->todaysStats['average_bill'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Total Bills</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ $this->todaysStats['bills'] }}</p>
            <p class="mt-1 text-xs text-slate-400">Completed settlements today</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Payment Mix</p>
            <div class="mt-3 space-y-2">
                @forelse ($this->todaysStats['modes'] as $mode)
                    <div>
                        <div class="flex justify-between text-xs font-bold text-slate-600">
                            <span class="uppercase">{{ $mode['label'] }}</span>
                            <span>{{ $mode['percentage'] }}% · ₹{{ number_format((float) $mode['revenue'], 2) }}</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full rounded-full bg-slate-100">
                            <div class="h-1.5 rounded-full {{ $mode['label'] === 'cash' ? 'bg-emerald-500' : ($mode['label'] === 'upi' ? 'bg-blue-500' : 'bg-amber-500') }}"
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
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-100 bg-slate-50 px-5 py-3">
            <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Recent Bills</h2>
        </header>
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-wider text-slate-400">
                <tr>
                    <th class="px-5 py-2 font-bold">Bill No.</th>
                    <th class="px-5 py-2 font-bold">Cashier</th>
                    <th class="px-5 py-2 font-bold">Payment</th>
                    <th class="px-5 py-2 font-bold">Time</th>
                    <th class="px-5 py-2 text-right font-bold">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->recentBills as $bill)
                    <tr wire:key="bill-{{ $bill->id }}" class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono text-xs font-bold text-slate-700">{{ $bill->order_number }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $bill->user?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase text-slate-600">{{ $bill->payment_mode->value }}</span>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $bill->created_at?->format('h:i A') }}</td>
                        <td class="px-5 py-3 text-right font-black text-slate-900">₹{{ $bill->total_amount }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No bills yet — the POS feed will appear here live.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
