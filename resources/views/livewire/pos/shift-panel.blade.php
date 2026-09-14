<div class="flex min-h-screen flex-col bg-slate-950 text-slate-100">
    <header class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-4 py-3">
        <p class="text-sm font-bold">Shift / Cash Drawer</p>
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.billing') }}" class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-black uppercase text-slate-950">← Billing</a>
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('admin.dashboard') }}"
                   class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 transition hover:border-emerald-500 hover:text-emerald-400">Admin</a>
            @endif
            {{-- The shift page previously had NO logout control at all —
                 staff on the cash-drawer screen had to hop back to Billing
                 just to sign out. --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-slate-400 hover:text-red-400">Logout</button>
            </form>
        </div>
    </header>

    <main class="mx-auto w-full max-w-3xl space-y-4 px-4 py-6">
        @if ($error)
            <p class="rounded-md bg-red-500/15 px-4 py-2 text-sm font-semibold text-red-300" role="alert">{{ $error }}</p>
        @endif
        @if ($success)
            <p class="rounded-md bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-300">{{ $success }}</p>
        @endif

        @if ($this->openShift)
            <section class="rounded-xl border border-emerald-500/30 bg-slate-900 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-emerald-300">Shift open since {{ $this->openShift->opened_at?->format('h:i A') }}</p>
                <p class="mt-1 text-sm text-slate-300">Opening float: ₹{{ $this->openShift->opening_cash }}</p>
                <div class="mt-3 flex gap-2">
                    <input wire:model="closingCash" type="number" min="0" step="0.01" placeholder="Counted closing cash"
                           class="flex-1 rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm" />
                    <button wire:click="close({{ $this->openShift->id }})"
                            class="rounded-lg bg-emerald-500 px-4 py-2 text-xs font-black uppercase text-slate-950">Close shift</button>
                </div>
            </section>
        @else
            <section class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Open a shift</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <input wire:model="openingCash" type="number" min="0" step="0.01" placeholder="Opening float ₹"
                           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm" />
                    <input wire:model="notes" type="text" maxlength="200" placeholder="Note (optional)"
                           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm" />
                </div>
                <button wire:click="open"
                        class="mt-3 w-full rounded-lg bg-emerald-500 px-4 py-2 text-xs font-black uppercase text-slate-950">Open shift</button>
            </section>
        @endif

        <section class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Recent shifts</p>
            <div class="mt-2 divide-y divide-slate-800 text-sm">
                @forelse ($this->recentShifts as $shift)
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-300">{{ $shift->user?->name }} · {{ $shift->opened_at?->format('d M h:i A') }}</span>
                        <span class="text-xs font-bold {{ $shift->status === 'open' ? 'text-emerald-300' : 'text-slate-400' }}">
                            {{ strtoupper($shift->status) }}
                            @if ($shift->status === 'closed')
                                · counted ₹{{ $shift->closing_cash }} / expected ₹{{ $shift->expected_cash }}
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-slate-500">No shifts yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</div>
