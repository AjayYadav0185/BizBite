{{-- Kitchen Order Ticket — no prices, big quantities, for the kitchen
     printer / pass. Input: $r = OrderReceipt::toArray(). --}}
@php
    $types = ['dine_in' => 'DINE-IN', 'takeaway' => 'TAKEAWAY', 'parcel' => 'PARCEL', 'delivery' => 'DELIVERY'];
@endphp
<div class="bb-paper" style="background:#fff;color:#000;width:72mm;max-width:100%;margin:0 auto;font-family:'Courier New',ui-monospace,monospace;font-size:13px;line-height:1.35">
    <div style="text-align:center;font-size:16px;font-weight:800;letter-spacing:2px">KOT</div>
    <div style="text-align:center;font-size:14px;font-weight:800">
        {{ $types[$r['order_type'] ?? ''] ?? 'ORDER' }}@if (filled($r['table_number'] ?? null)) · TABLE {{ $r['table_number'] }}@endif
    </div>
    <div style="border-top:1px dashed #000;margin:4px 0"></div>
    <div style="display:flex;justify-content:space-between"><span>#{{ $r['order_number'] }}</span><span>{{ $r['placed_at'] }}</span></div>
    @if (filled($r['cashier'] ?? null))<div>By: {{ $r['cashier'] }}</div>@endif
    <div style="border-top:1px dashed #000;margin:4px 0"></div>
    @foreach ($r['items'] as $item)
        <div style="display:flex;gap:8px;font-size:15px;font-weight:700;padding:1px 0">
            <span style="width:34px;text-align:right">{{ $item['quantity'] }} x</span>
            <span style="flex:1;overflow-wrap:anywhere">{{ $item['food_item_name'] }}</span>
        </div>
    @endforeach
    <div style="border-top:1px dashed #000;margin:4px 0"></div>
    @if (filled($r['notes'] ?? null))
        <div style="font-size:14px;font-weight:800">NOTE: {{ $r['notes'] }}</div>
        <div style="border-top:1px dashed #000;margin:4px 0"></div>
    @endif
    <div style="text-align:center">Total items: {{ $r['total_quantity'] ?? count($r['items']) }}</div>
</div>
