<div class="flex min-h-screen flex-col bg-bg text-ink">
    @include('partials.pos-nav', ['active' => 'shift'])

    <main class="mx-auto w-full max-w-3xl space-y-4 px-4 py-6">
        @if ($error)
            <p class="rounded-md bg-red-500/15 px-4 py-2 text-sm font-semibold text-red-700 dark:text-red-300" role="alert">{{ $error }}</p>
        @endif
        @if ($success)
            <p class="rounded-md bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $success }}</p>
        @endif

        @if ($this->openShift)
            <section class="rounded-xl border border-emerald-500/30 bg-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Shift open since {{ $this->openShift->opened_at?->format('h:i A') }}</p>
                <p class="mt-1 text-sm text-ink-2">Opening float: ₹{{ $this->openShift->opening_cash }}</p>
                <div class="mt-3 flex gap-2">
                    <input wire:model="closingCash" type="number" min="0" step="0.01" placeholder="Counted closing cash"
                           class="flex-1 rounded-lg border border-line-strong bg-surface-2 px-3 py-2 text-sm" />
                    <button wire:click="close({{ $this->openShift->id }})"
                            class="rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white">Close shift</button>
                </div>
            </section>
        @else
            <section class="rounded-xl border border-line bg-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-3">Open a shift</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <input wire:model="openingCash" type="number" min="0" step="0.01" placeholder="Opening float ₹"
                           class="rounded-lg border border-line-strong bg-surface-2 px-3 py-2 text-sm" />
                    <input wire:model="notes" type="text" maxlength="200" placeholder="Note (optional)"
                           class="rounded-lg border border-line-strong bg-surface-2 px-3 py-2 text-sm" />
                </div>
                <button wire:click="open"
                        class="mt-3 w-full rounded-lg bg-brand-600 px-4 py-2 text-xs font-semibold text-white">Open shift</button>
            </section>
        @endif

        <section class="rounded-xl border border-line bg-surface p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-3">Recent shifts</p>
            <div class="mt-2 divide-y divide-line text-sm">
                @forelse ($this->recentShifts as $shift)
                    <div class="flex items-center justify-between py-2">
                        <span class="text-ink-2">{{ $shift->user?->name }} · {{ $shift->opened_at?->format('d M h:i A') }}</span>
                        <span class="text-xs font-bold {{ $shift->status === 'open' ? 'text-emerald-700 dark:text-emerald-300' : 'text-ink-3' }}">
                            {{ strtoupper($shift->status) }}
                            @if ($shift->status === 'closed')
                                · counted ₹{{ $shift->closing_cash }} / expected ₹{{ $shift->expected_cash }}
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-ink-3">No shifts yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</div>
