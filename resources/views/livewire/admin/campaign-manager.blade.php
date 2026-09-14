<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900">Discount Campaigns</h1>
        <p class="text-sm text-slate-500">Loyalty codes · cashier types the code, server validates + snapshots the discount</p>
    </div>

    @if ($error)
        <p class="rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-600" role="alert">{{ $error }}</p>
    @endif
    @if ($success)
        <p class="rounded-xl bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $success }}</p>
    @endif

    <div class="grid gap-2 rounded-2xl border border-card-border bg-white p-4 shadow-card sm:grid-cols-2 lg:grid-cols-6">
        <label class="text-xs font-bold text-slate-500">Name
            <input wire:model="name" type="text" maxlength="120" placeholder="Festive 10% off" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">Code
            <input wire:model="code" type="text" maxlength="40" placeholder="DIWALI10" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm uppercase" />
        </label>
        <label class="text-xs font-bold text-slate-500">Type
            <select wire:model="type" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm">
                <option value="percent">Percent %</option>
                <option value="flat">Flat ₹</option>
            </select>
        </label>
        <label class="text-xs font-bold text-slate-500">Value
            <input wire:model="value" type="number" min="0.01" step="0.01" placeholder="10" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">Min order ₹
            <input wire:model="minOrder" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <button wire:click="create" class="self-end rounded-xl bg-brand-500 px-4 py-2 text-sm font-bold text-white">Create</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-card-border bg-white shadow-card">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="px-4 py-2">Code</th><th class="px-4 py-2">Name</th><th class="px-4 py-2">Offer</th><th class="px-4 py-2">Min</th><th class="px-4 py-2">Status</th><th class="px-4 py-2 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-surface-subtle">
                @forelse ($this->campaigns as $campaign)
                    <tr>
                        <td class="px-4 py-2 font-mono font-bold">{{ $campaign->code }}</td>
                        <td class="px-4 py-2">{{ $campaign->name }}</td>
                        <td class="px-4 py-2 font-bold">{{ $campaign->type === 'flat' ? '₹'.$campaign->value : $campaign->value.'%' }}</td>
                        <td class="px-4 py-2">₹{{ $campaign->min_order_amount }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-black uppercase {{ $campaign->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $campaign->is_active ? 'Live' : 'Off' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="toggle({{ $campaign->id }})" class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold">Toggle</button>
                            <button wire:click="delete({{ $campaign->id }})" wire:confirm="Delete {{ $campaign->code }}?" class="rounded-lg px-2.5 py-1 text-xs font-bold text-red-500">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No campaigns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
