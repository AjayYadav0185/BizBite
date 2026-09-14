<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Reports & Analytics</h1>
            <p class="text-sm text-slate-500">Hourly sales · best-sellers · date range · CSV export</p>
        </div>
        <button wire:click="exportCsv" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-bold text-white shadow-sm">Export CSV</button>
    </div>

    <div class="grid gap-2 rounded-2xl border border-card-border bg-white p-4 shadow-card sm:grid-cols-4">
        <label class="text-xs font-bold text-slate-500">Day (hourly)
            <input wire:model.live="date" type="date" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">From
            <input wire:model.live="from" type="date" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">To
            <input wire:model.live="to" type="date" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">Top N
            <input wire:model.live="limit" type="number" min="1" max="50" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-card-border bg-white p-4 shadow-card">
            <p class="text-xs font-black uppercase text-slate-400">Revenue</p>
            <p class="mt-1 text-2xl font-black text-brand-600">₹{{ $this->rangeStats['revenue'] }}</p>
        </div>
        <div class="rounded-2xl border border-card-border bg-white p-4 shadow-card">
            <p class="text-xs font-black uppercase text-slate-400">Bills</p>
            <p class="mt-1 text-2xl font-black text-brand-600">{{ $this->rangeStats['bills'] }}</p>
        </div>
        <div class="rounded-2xl border border-card-border bg-white p-4 shadow-card">
            <p class="text-xs font-black uppercase text-slate-400">Discounts</p>
            <p class="mt-1 text-2xl font-black text-warn-600">₹{{ $this->rangeStats['discounts'] }}</p>
        </div>
        <div class="rounded-2xl border border-card-border bg-white p-4 shadow-card">
            <p class="text-xs font-black uppercase text-slate-400">Refunds</p>
            <p class="mt-1 text-2xl font-black text-red-500">₹{{ $this->rangeStats['refunds'] }}</p>
        </div>
        <div class="rounded-2xl border border-card-border bg-white p-4 shadow-card">
            <p class="text-xs font-black uppercase text-slate-400">Net (rev − refunds)</p>
            <p class="mt-1 text-2xl font-black text-emerald-600">₹{{ $this->rangeStats['net'] }}</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-card-border bg-white shadow-card">
            <header class="border-b border-surface-subtle bg-surface-muted px-4 py-3">
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Hourly sales · {{ $date }}</h2>
            </header>
            <div class="max-h-80 overflow-y-auto p-4">
                @foreach ($this->hourly as $row)
                    <div class="flex items-center gap-3 py-1 text-sm">
                        <span class="w-12 font-mono text-xs text-slate-500">{{ $row['hour'] }}</span>
                        <div class="h-2 flex-1 rounded-full bg-surface-muted">
                            @php $peak = max(array_column($this->hourly, 'revenue')) ?: 1; @endphp
                            <div class="h-2 rounded-full bg-brand-500" style="width: {{ min(100, ((float) $row['revenue']) / ((float) $peak) * 100) }}%"></div>
                        </div>
                        <span class="w-28 text-right tabular-nums text-xs font-bold text-slate-600">₹{{ $row['revenue'] }} · {{ $row['bills'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-card-border bg-white shadow-card">
            <header class="border-b border-surface-subtle bg-surface-muted px-4 py-3">
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Best-sellers · {{ $from }} → {{ $to }}</h2>
            </header>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-400"><tr><th class="px-4 py-2">#</th><th class="px-4 py-2">Item</th><th class="px-4 py-2 text-right">Qty</th><th class="px-4 py-2 text-right">Revenue</th></tr></thead>
                <tbody class="divide-y divide-surface-subtle">
                    @forelse ($this->bestSellers as $i => $item)
                        <tr><td class="px-4 py-2 font-bold text-slate-400">{{ $i + 1 }}</td><td class="px-4 py-2 font-bold">{{ $item['name'] }}</td><td class="px-4 py-2 text-right tabular-nums">{{ $item['quantity'] }}</td><td class="px-4 py-2 text-right tabular-nums font-bold text-brand-600">₹{{ $item['revenue'] }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No sales in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
