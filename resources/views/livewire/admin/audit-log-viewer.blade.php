<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-black text-slate-900">Audit Logs</h2>
            <p class="text-xs text-slate-500">Who changed what, when — prices, discounts, credit bills, settings, staff. Owner eyes only.</p>
        </div>
        <button wire:click="clearFilters" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100">
            Clear filters
        </button>
    </div>

    <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
        <label class="block">
            <span class="text-xs font-bold text-slate-500">Search</span>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Paneer Tikka, Priya, bill no…"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none" />
        </label>
        <label class="block">
            <span class="text-xs font-bold text-slate-500">Action</span>
            <select wire:model.live="action"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                <option value="">All actions</option>
                @foreach ($this->actionOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="text-xs font-bold text-slate-500">Staff</span>
            <select wire:model.live="staffId"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
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

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Who</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Detail</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
                            {{ $log->created_at?->format('d M Y, h:i A') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-800">
                            {{ $log->user_name ?? $log->user?->name ?? 'System' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-[11px] font-black
                                @if ($log->action === 'price_updated') bg-amber-100 text-amber-800
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
                                    <summary class="cursor-pointer font-bold">old → new</summary>
                                    <pre class="mt-1 overflow-x-auto rounded bg-slate-100 p-2">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-slate-900">
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

    <div>{{ $logs->links() }}</div>
</div>
