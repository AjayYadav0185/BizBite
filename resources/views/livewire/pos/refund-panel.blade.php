{{-- POS counter: partial refunds with mandatory reason (must-add #3). --}}
<div class="rounded-xl border border-line bg-surface p-4">
    <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-3">Partial Refund</h3>

    @if ($error)
        <p class="mt-2 rounded-md bg-red-500/15 px-3 py-2 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $error }}</p>
    @endif
    @if ($success)
        <p class="mt-2 rounded-md bg-emerald-500/15 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ $success }}</p>
    @endif

    @if ($order)
        <p class="mt-2 text-sm text-ink-2">
            Bill <span class="font-mono font-bold text-ink">{{ $order->order_number }}</span>
            · Total ₹{{ $order->total_amount }}
            · Refunded ₹{{ $order->refunded_amount ?? '0.00' }}
        </p>
    @endif

    <div class="mt-3 grid gap-2 sm:grid-cols-2">
        <label class="text-xs font-bold text-ink-3">Amount (₹)
            <input wire:model="amount" type="number" min="0.01" step="0.01" placeholder="0.00"
                   class="mt-1 w-full rounded-lg border border-line-strong bg-surface-2 px-3 py-2 text-sm text-ink" />
        </label>
        <label class="text-xs font-bold text-ink-3">Mode
            <select wire:model="mode" class="mt-1 w-full rounded-lg border border-line-strong bg-surface-2 px-3 py-2 text-sm text-ink">
                @foreach (\App\Models\Enums\PaymentMode::cases() as $m)
                    <option value="{{ $m->value }}">{{ ucfirst($m->value) }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <label class="mt-2 block text-xs font-bold text-ink-3">Reason (required)
        <input wire:model="reason" type="text" maxlength="200" placeholder="e.g. spilled curry — remade portion"
               class="mt-1 w-full rounded-lg border border-line-strong bg-surface-2 px-3 py-2 text-sm text-ink" />
    </label>

    <button wire:click="issueRefund"
            class="mt-3 w-full rounded-lg border border-amber-500/40 px-3 py-2 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300 transition hover:bg-amber-500/10">
        Issue Refund
    </button>

    @if ($order && $order->refunds->isNotEmpty())
        <ul class="mt-3 space-y-1 text-xs text-ink-3">
            @foreach ($order->refunds as $refund)
                <li>−₹{{ $refund->amount }} ({{ $refund->mode }}) — {{ $refund->reason }}</li>
            @endforeach
        </ul>
    @endif
</div>
