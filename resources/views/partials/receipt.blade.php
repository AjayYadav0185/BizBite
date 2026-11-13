{{-- =====================================================================
     80mm thermal TAX INVOICE (GST) — one template for print, preview and
     reprint. Input: $r = OrderReceipt::toArray().

     Inline colours keep the paper black-on-white even in dark mode.
     Registered stores print "TAX INVOICE" + GSTIN + CGST/SGST per slab +
     SAC 996331 (restaurant service). Unregistered stores print a plain
     "BILL OF SUPPLY" with no tax lines, as GST rules require.
====================================================================== --}}
@php

    $s = $r['store'] ?? [];
    $gst = ! empty($s['gst_enabled']) && filled($s['gstin'] ?? null);
    $types = ['dine_in' => 'Dine-in', 'takeaway' => 'Takeaway', 'parcel' => 'Parcel', 'delivery' => 'Delivery'];
    $hr = '<div style="border-top:1px dashed #000;margin:4px 0"></div>';
    $num = fn ($v) => \App\Support\India::number($v);
@endphp
<div class="bb-paper" style="background:#fff;color:#000;width:72mm;max-width:100%;margin:0 auto;font-family:'Courier New',ui-monospace,monospace;font-size:12px;line-height:1.35">
    <div style="text-align:center">
        @if (filled($s['print_header'] ?? null))
            <div style="font-size:11px;font-weight:700;text-transform:uppercase">{{ $s['print_header'] }}</div>
        @endif
        <div style="font-size:15px;font-weight:800;text-transform:uppercase">{{ $s['name'] ?? '' }}</div>
        @if (filled($s['address'] ?? null))
            @php
                $addr = $s['address'];
                foreach (['city' => ', ', 'pincode' => ' - '] as $k => $sep) {
                    if (filled($s[$k] ?? null) && ! str_contains($addr, $s[$k])) { $addr .= $sep.$s[$k]; }
                }
            @endphp
            <div>{{ $addr }}</div>
        @endif
        @if (filled($s['phone'] ?? null))<div>Ph: {{ $s['phone'] }}</div>@endif
        @if ($gst)<div style="font-weight:700">GSTIN: {{ $s['gstin'] }}</div>@endif
        @if (filled($s['fssai'] ?? null))<div>FSSAI Lic. No: {{ $s['fssai'] }}</div>@endif
        <div style="margin-top:4px;font-weight:800;letter-spacing:1px">{{ $gst ? 'TAX INVOICE' : 'BILL OF SUPPLY' }}</div>
        @if (! empty($reprint))<div style="font-size:11px">** DUPLICATE COPY **</div>@endif
    </div>

    {!! $hr !!}
    <div style="display:flex;justify-content:space-between"><span>Bill No:</span><span style="font-weight:700">{{ $r['order_number'] }}</span></div>
    <div style="display:flex;justify-content:space-between"><span>Date:</span><span>{{ $r['placed_at'] }}</span></div>
    <div style="display:flex;justify-content:space-between">
        <span>{{ $types[$r['order_type'] ?? ''] ?? 'Order' }}@if (filled($r['table_number'] ?? null)) · Table {{ $r['table_number'] }}@endif</span>
        @if (filled($r['cashier'] ?? null))<span>{{ $r['cashier'] }}</span>@endif
    </div>
    @if (filled($r['customer_name'] ?? null) || filled($r['customer_phone'] ?? null))
        <div>Customer: {{ $r['customer_name'] ?? '' }} {{ filled($r['customer_phone'] ?? null) ? '('.$r['customer_phone'].')' : '' }}</div>
    @endif
    @if (filled($r['delivery_address'] ?? null))
        <div>Deliver to: {{ $r['delivery_address'] }}</div>
    @endif

    {!! $hr !!}
    <div style="display:flex;font-weight:700">
        <span style="flex:1">Item</span><span style="width:26px;text-align:right">Qty</span><span style="width:52px;text-align:right">Rate</span><span style="width:62px;text-align:right">Amt</span>
    </div>
    {!! $hr !!}
    @foreach ($r['items'] as $item)
        <div style="display:flex;align-items:flex-start">
            <span style="flex:1;min-width:0;overflow-wrap:anywhere">{{ $item['food_item_name'] }}</span>
            <span style="width:26px;text-align:right">{{ $item['quantity'] }}</span>
            <span style="width:52px;text-align:right">{{ $num($item['price']) }}</span>
            <span style="width:62px;text-align:right">{{ $num($item['subtotal']) }}</span>
        </div>
    @endforeach

    {!! $hr !!}
    <div style="display:flex;justify-content:space-between"><span>Items: {{ $r['total_quantity'] ?? count($r['items']) }}</span><span>Sub Total: {{ $num($r['subtotal']) }}</span></div>
    @if (bccomp((string) ($r['discount_amount'] ?? '0'), '0', 2) > 0)
        <div style="display:flex;justify-content:space-between">
            <span>Discount{{ filled($r['campaign_code'] ?? null) ? ' ('.$r['campaign_code'].')' : '' }}</span>
            <span>-{{ $num($r['discount_amount']) }}</span>
        </div>
    @endif

    @if ($gst && ! empty($r['tax_breakup']))
        {!! $hr !!}
        <div style="display:flex;font-size:11px;font-weight:700">
            <span style="flex:1">GST%</span><span style="width:62px;text-align:right">Taxable</span><span style="width:52px;text-align:right">CGST</span><span style="width:52px;text-align:right">SGST</span>
        </div>
        @foreach ($r['tax_breakup'] as $row)
            <div style="display:flex;font-size:11px">
                <span style="flex:1">{{ $row['rate'] }}%</span>
                <span style="width:62px;text-align:right">{{ $num($row['taxable']) }}</span>
                <span style="width:52px;text-align:right">{{ $num($row['cgst']) }}</span>
                <span style="width:52px;text-align:right">{{ $num($row['sgst']) }}</span>
            </div>
        @endforeach
        <div style="font-size:10px">Prices inclusive of GST · SAC {{ $r['sac_code'] ?? \App\Support\India::SAC_RESTAURANT }}</div>
    @endif

    @if (bccomp((string) ($r['round_off'] ?? '0'), '0', 2) !== 0)
        <div style="display:flex;justify-content:space-between"><span>Round off</span><span>{{ $r['round_off'] }}</span></div>
    @endif
    {!! $hr !!}
    <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800">
        <span>TOTAL</span><span>Rs. {{ $num($r['total_amount']) }}</span>
    </div>
    @if (filled($r['amount_in_words'] ?? null))
        <div style="font-size:10px;font-style:italic">{{ $r['amount_in_words'] }}</div>
    @endif
    {!! $hr !!}

    <div style="display:flex;justify-content:space-between;text-transform:uppercase"><span>Paid via</span><span style="font-weight:700">{{ $r['payment_mode'] }}</span></div>
    @if (bccomp((string) ($r['tendered_amount'] ?? '0'), '0', 2) > 0)
        <div style="display:flex;justify-content:space-between"><span>Tendered</span><span>{{ $num($r['tendered_amount']) }}</span></div>
        <div style="display:flex;justify-content:space-between"><span>Change</span><span>{{ $num($r['change_amount'] ?? 0) }}</span></div>
    @endif
    @if (filled($r['upi_ref'] ?? null))
        <div style="display:flex;justify-content:space-between"><span>UPI Ref</span><span>{{ $r['upi_ref'] }}</span></div>
    @endif
    @if (($r['payment_mode'] ?? '') === 'credit')
        <div style="font-weight:700;text-align:center;margin-top:2px">*** PAYMENT DUE (CREDIT) ***</div>
    @endif
    @if (filled($r['notes'] ?? null))
        <div style="margin-top:2px">Note: {{ $r['notes'] }}</div>
    @endif

    {!! $hr !!}
    <div style="text-align:center">
        <div>{{ filled($s['print_footer'] ?? null) ? $s['print_footer'] : 'Thank you! Visit again.' }}</div>
        <div style="font-size:10px;margin-top:4px">Powered by BizBite POS</div>
    </div>
</div>
