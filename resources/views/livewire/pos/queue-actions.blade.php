{{-- Queue-board side panel: partial refund + delivery moves for one bill. --}}
<div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">
        Bill {{ $order?->order_number ?? '#'.$orderId }} · actions
    </h3>

    @if ($error)
        <p class="mt-2 rounded-md bg-red-500/15 px-3 py-2 text-xs font-semibold text-red-300" role="alert">{{ $error }}</p>
    @endif
    @if ($success)
        <p class="mt-2 rounded-md bg-emerald-500/15 px-3 py-2 text-xs font-semibold text-emerald-300">{{ $success }}</p>
    @endif

    @if ($order)
        <p class="mt-2 text-xs text-slate-400">
            Total ₹{{ $order->total_amount }} · refunded ₹{{ $order->refunded_amount ?? '0.00' }}
            @if ($order->order_type->value === 'delivery') · delivery: {{ $order->delivery_status }} @endif
        </p>

        <div class="mt-3 border-t border-dashed border-slate-800 pt-3">
            <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Partial refund</p>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <input wire:model="refundAmount" type="number" min="0.01" step="0.01" placeholder="Amount ₹"
                       class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-sm" />
                <select wire:model="refundMode" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-sm">
                    @foreach (\App\Models\Enums\PaymentMode::cases() as $m)
                        <option value="{{ $m->value }}">{{ ucfirst($m->value) }}</option>
                    @endforeach
                </select>
            </div>
            <input wire:model="refundReason" type="text" maxlength="200" placeholder="Reason (required)"
                   class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-sm" />
            <button wire:click="issueRefund"
                    class="mt-2 w-full rounded-lg border border-amber-500/40 px-3 py-1.5 text-xs font-bold text-amber-300 hover:bg-amber-500/10">
                Issue refund
            </button>
        </div>

        @if ($order->order_type->value === 'delivery')
            <div class="mt-3 border-t border-dashed border-slate-800 pt-3">
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">Delivery workflow</p>
                @if (filled($order->delivery_address))
                    <p class="mt-1 text-xs text-slate-400">📍 {{ $order->delivery_address }}</p>
                @endif
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <select wire:model="deliveryStatus" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-sm">
                        <option value="">Select status…</option>
                        <option value="assigned">Assigned</option>
                        <option value="out">Out for delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="failed">Failed</option>
                    </select>
                    <input wire:model="deliveryAgent" type="text" maxlength="80" placeholder="Agent (optional)"
                           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-sm" />
                </div>
                <button wire:click="moveDelivery"
                        class="mt-2 w-full rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-200 hover:bg-slate-700">
                    Update delivery
                </button>
            </div>
        @endif
    @else
        <p class="mt-2 text-xs text-slate-500">Bill not found.</p>
    @endif
</div>
