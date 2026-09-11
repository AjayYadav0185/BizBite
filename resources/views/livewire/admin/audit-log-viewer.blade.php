<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-black text-slate-900">Audit Logs</h2>
            <p class="text-xs text-slate-500">Who changed what, when — prices, discounts, credit bills, settings, staff. Owner eyes only.</p>
        </div>
        <button wire:click="clearFilters"
                class="rounded-xl border border-card-border bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow-sm hover:bg-surface-subtle">
            Clear filters
        </button>
    </div>

    <div class="grid grid-cols-1 gap-3 rounded-2xl border border-card-border bg-white p-4 shadow-card md:grid-cols-2 xl:grid-cols-4">
        <label class="block">
            <span class="text-xs font-bold text-slate-500">Search</span>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Paneer Tikka, Priya, bill no…"
                   class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm outline-none transition focus:border-brand-500" />
        </label>
        <label class="block">
            <span class="text-xs font-bold text-slate-500">Action</span>
            <select wire:model.live="action"
                    class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm outline-none transition focus:border-brand-500">
                <option value="">All actions</option>
                @foreach ($this->actionOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="text-xs font-bold text-slate-500">Staff</span>
            <select wire:model.live="staffId"
                    class="mt-1 w-full rounded-xl border border-card-border px-3 py-2 text-sm outline-none transition focus:border-brand-500">
                <option value="">All staff</option>
                @foreach ($this->staffList as $staff)
                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end">
            <p class="text-xs text-slate-400">{{ $logs->total() }} entr{{ $logs->total() === 1 ? 'y' : 'ies' }} · newest first</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-card-border bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Who</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Detail</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-subtle">
                @forelse ($logs as $log)
                    <tr class="align-top hover:bg-brand-50/60">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
                            {{ $log->created_at?->format('d M Y, h:i A') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-800">
                            {{ $log->user_name ?? $log->user?->name ?? 'System' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-[11px] font-black
                                @if ($log->action === 'price_updated') bg-warn-50 text-warn-600
                                @elseif ($log->action === 'credit_bill') bg-red-100 text-red-700
                                @elseif ($log->action === 'order_discount') bg-violet-100 text-violet-700
                                @elseif ($log->action === 'order_cancelled') bg-red-50 text-red-600
                                @elseif (in_array($log->action, ['item_deleted', 'category_deleted'])) bg-slate-200 text-slate-700
                                @else bg-emerald-50 text-emerald-700 @endif">
                                {{ $this->actionOptions[$log->action] ?? $log->action }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ $log->description }}
                            @if (is_array($log->old_values) || is_array($log->new_values))
                                <details class="mt-1 text-xs text-slate-500">
                                    <summary class="cursor-pointer font-bold text-brand-600">old → new</summary>
                                        <pre class="mt-1 overflow-x-auto rounded-xl bg-surface-muted p-2">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums text-slate-900">
                            @if ($log->amount !== null) ₹{{ number_format((float) $log->amount, 2) }} @else <span class="text-slate-300">—</span> @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-400">
                            No log entries yet. Price changes, discounts and credit bills will appear here.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
