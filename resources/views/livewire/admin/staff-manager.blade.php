<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900">Staff</h1>
        <p class="text-sm text-slate-500">Cashiers · shifts are opened/closed on the POS, audited here</p>
    </div>

    @if ($error)
        <p class="rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-600" role="alert">{{ $error }}</p>
    @endif
    @if ($success)
        <p class="rounded-xl bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $success }}</p>
    @endif

    <div class="grid gap-2 rounded-2xl border border-card-border bg-white p-4 shadow-card sm:grid-cols-2 lg:grid-cols-5">
        <label class="text-xs font-bold text-slate-500">Name
            <input wire:model="name" type="text" placeholder="Ravi Kumar" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">Email
            <input wire:model="email" type="email" placeholder="ravi@outlet.in" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">Phone
            <input wire:model="phone" type="text" maxlength="20" placeholder="98XXXXXXXX" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-500">Password
            <input wire:model="password" type="password" placeholder="min 6 chars" class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm" />
        </label>
        <button wire:click="create" class="self-end rounded-xl bg-brand-500 px-4 py-2 text-sm font-bold text-white">Add cashier</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-card-border bg-white shadow-card">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-400">
                <tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Role</th><th class="px-4 py-2">Last login</th><th class="px-4 py-2">Status</th><th class="px-4 py-2 text-right">Action</th></tr>
            </thead>
            <tbody class="divide-y divide-surface-subtle">
                @forelse ($this->staff as $member)
                    <tr>
                        <td class="px-4 py-2 font-bold text-slate-800">{{ $member->name }}<br /><span class="text-xs font-normal text-slate-400">{{ $member->email }}</span></td>
                        <td class="px-4 py-2 uppercase text-xs font-bold text-slate-500">{{ $member->role->value }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $member->last_login_at?->format('d M h:i A') ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-black uppercase {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-500' }}">
                                {{ $member->is_active ? 'Active' : 'Off' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="toggleActive({{ $member->id }})" class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold">
                                {{ $member->is_active ? 'Deactivate' : 'Reactivate' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No staff yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
