<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900">Dining Tables</h1>
        <p class="text-sm text-slate-500">Dine-in tables · occupied automatically by POS bills, freed on completion</p>
    </div>

    @if ($error)
        <p class="rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-600" role="alert">{{ $error }}</p>
    @endif
    @if ($success)
        <p class="rounded-xl bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $success }}</p>
    @endif

    <div class="flex flex-wrap items-end gap-2">
        <label class="text-xs font-bold text-slate-500">Table no.
            <input wire:model="tableNumber" type="text" maxlength="20" placeholder="T1"
                   class="mt-1 w-32 rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">Seats
            <input wire:model="seats" type="number" min="1" max="50"
                   class="mt-1 w-24 rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <button wire:click="create" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-bold text-white">Add table</button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($this->tables as $table)
            <div class="rounded-2xl border border-card-border bg-white p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <p class="text-lg font-black text-slate-900">{{ $table->table_number }}</p>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-black uppercase
                        {{ $table->status === 'available' ? 'bg-emerald-50 text-emerald-700' : ($table->status === 'occupied' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700') }}">
                        {{ $table->status }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ $table->seats }} seats
                    @if ($table->current_order_id) · bill #{{ $table->current_order_id }} @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <button wire:click="setStatus({{ $table->id }}, 'available')" class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold">Free</button>
                    <button wire:click="setStatus({{ $table->id }}, 'reserved')" class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold">Hold</button>
                    <button wire:click="delete({{ $table->id }})" wire:confirm="Delete table {{ $table->table_number }}?" class="rounded-lg px-2.5 py-1 text-xs font-bold text-red-500">Delete</button>
                </div>
            </div>
        @empty
            <p class="col-span-full rounded-2xl border border-dashed border-card-border p-8 text-center text-sm text-slate-400">No tables yet — add T1, T2…</p>
        @endforelse
    </div>
</div>
