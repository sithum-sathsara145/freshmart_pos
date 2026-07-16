{{-- purchases/bill_pdf.blade.php — A5 PDF via DomPDF --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #111;
    background: #fff;
    padding: 22px;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 2px solid #1a1a2e;
}
.biz-name { font-size: 20px; font-weight: 700; color: #1a1a2e; }
.biz-sub  { font-size: 10px; color: #666; margin-top: 2px; line-height: 1.5; }
.bill-title { font-size: 22px; font-weight: 700; color: #1e3a5f; text-align: right; }
.bill-no    { font-size: 11px; color: #666; text-align: right; margin-top: 3px; }

/* Status badge */
.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    margin-top: 5px;
}

/* Parties */
.parties {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 16px;
}
.party-box {
    flex: 1;
    background: #f8f9fc;
    border-left: 3px solid #1e3a5f;
    padding: 8px 12px;
    border-radius: 0 5px 5px 0;
}
.party-lbl  { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
.party-name { font-size: 12px; font-weight: 600; color: #111; }
.party-sub  { font-size: 10px; color: #555; margin-top: 1px; }

/* Meta table */
.meta-table { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
.meta-table td { padding: 3px 8px; font-size: 10px; }
.meta-table td:first-child { color: #888; width: 90px; }
.meta-table td:last-child  { color: #111; font-weight: 500; }

/* Items table */
table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
table.items thead tr { background: #1e3a5f; color: #fff; }
table.items thead th { padding: 7px 10px; text-align: left; font-size: 10px; font-weight: 600; }
table.items thead th.right { text-align: right; }
table.items tbody tr { border-bottom: 1px solid #eee; }
table.items tbody td { padding: 6px 10px; font-size: 11px; }
table.items tbody td.right { text-align: right; }
table.items tbody tr:nth-child(even) { background: #f8f9fc; }

/* Totals */
.totals { float: right; width: 210px; }
.tot-row { display: flex; justify-content: space-between; font-size: 11px; padding: 3px 0; border-bottom: 1px solid #eee; }
.tot-row.grand {
    font-size: 14px; font-weight: 700;
    border-top: 2px solid #1e3a5f;
    border-bottom: none;
    padding-top: 6px;
    color: #1e3a5f;
}
.tot-row.due { color: #991b1b; font-weight: 600; }
.tot-row.paid { color: #166534; }

/* Footer */
.footer {
    margin-top: 34px;
    clear: both;
    border-top: 1px solid #ddd;
    padding-top: 10px;
    font-size: 10px;
    color: #888;
    display: flex;
    justify-content: space-between;
}
.sig-block { text-align: center; }
.sig-line  { border-top: 1px solid #ccc; margin-top: 24px; width: 120px; margin-left: auto; margin-right: auto; font-size: 9px; color: #999; padding-top: 3px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div>
        <div class="biz-name">{{ $settings['business_name'] ?? 'FreshMart Supermarket' }}</div>
        <div class="biz-sub">{{ $settings['address'] ?? 'No. 42, Main Street, Colombo 07' }}</div>
        <div class="biz-sub">{{ $settings['phone'] ?? '011-2345678' }}  &nbsp;|&nbsp;  {{ $settings['email'] ?? 'info@freshmart.lk' }}</div>
    </div>
    <div>
        <div class="bill-title">PURCHASE BILL</div>
        <div class="bill-no"># {{ $purchase->bill_no }}</div>
        <div class="bill-no">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</div>
        @php
            $sc = ['paid'=>['#dcfce7','#166534'],'partial'=>['#fef9c3','#854d0e'],'unpaid'=>['#fee2e2','#991b1b']][$purchase->payment_status] ?? ['#f1f5f9','#475569'];
        @endphp
        <div style="text-align:right;margin-top:5px">
            <span class="status-badge" style="background:{{ $sc[0] }};color:{{ $sc[1] }}">
                {{ strtoupper($purchase->payment_status) }}
            </span>
        </div>
    </div>
</div>

{{-- Parties --}}
<div class="parties">
    <div class="party-box">
        <div class="party-lbl">From (Supplier)</div>
        <div class="party-name">{{ $purchase->supplier?->name }}</div>
        @if($purchase->supplier?->contact_person)
        <div class="party-sub">{{ $purchase->supplier->contact_person }}</div>
        @endif
        @if($purchase->supplier?->phone)
        <div class="party-sub">{{ $purchase->supplier->phone }}</div>
        @endif
        @if($purchase->supplier?->city)
        <div class="party-sub">{{ $purchase->supplier->city }}</div>
        @endif
    </div>
    <div class="party-box">
        <div class="party-lbl">Bill to</div>
        <div class="party-name">{{ $settings['business_name'] ?? 'FreshMart Supermarket' }}</div>
        <div class="party-sub">{{ $purchase->branch?->name }}</div>
        <div class="party-sub">{{ $settings['phone'] ?? '' }}</div>
    </div>
</div>

{{-- Meta --}}
<table class="meta-table">
    <tr><td>Bill No.</td><td>{{ $purchase->bill_no }}</td><td style="width:20px"></td><td style="color:#888;width:90px">Purchase date</td><td style="font-weight:500">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</td></tr>
    <tr><td>Due date</td><td>{{ $purchase->due_date ? \Carbon\Carbon::parse($purchase->due_date)->format('d M Y') : '—' }}</td><td></td><td style="color:#888">Payment method</td><td style="font-weight:500">{{ ucfirst(str_replace('_',' ',$purchase->payment_method)) }}</td></tr>
    <tr><td>Created by</td><td>{{ $purchase->createdBy?->name ?? 'Admin' }}</td><td></td><td></td><td></td></tr>
</table>

{{-- Items table --}}
<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Unit</th>
            <th class="right">Qty</th>
            <th class="right">Unit price</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchase->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->product?->name ?? $item->name }}</td>
            <td>{{ $item->product?->unit ?? 'Pcs' }}</td>
            <td class="right">{{ number_format($item->quantity, 2) }}</td>
            <td class="right">Rs. {{ number_format($item->unit_price) }}</td>
            <td class="right">Rs. {{ number_format($item->subtotal) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<div class="totals">
    <div class="tot-row"><span>Subtotal</span><span>Rs. {{ number_format($purchase->subtotal) }}</span></div>
    @if($purchase->discount_amount > 0)
    <div class="tot-row"><span>Discount</span><span>- Rs. {{ number_format($purchase->discount_amount) }}</span></div>
    @endif
    @if($purchase->tax_amount > 0)
    <div class="tot-row"><span>Tax</span><span>Rs. {{ number_format($purchase->tax_amount) }}</span></div>
    @endif
    <div class="tot-row grand"><span>TOTAL</span><span>Rs. {{ number_format($purchase->total) }}</span></div>
    <div class="tot-row paid"><span>Amount paid</span><span>Rs. {{ number_format($purchase->paid_amount) }}</span></div>
    @if($purchase->balance_due > 0)
    <div class="tot-row due"><span>Balance due</span><span>Rs. {{ number_format($purchase->balance_due) }}</span></div>
    @endif
</div>

<div style="clear:both"></div>

{{-- Footer --}}
<div class="footer">
    <div>
        <div>Printed: {{ now()->format('d M Y H:i') }}</div>
        <div>Created by: {{ $purchase->createdBy?->name ?? 'System' }}</div>
        @if($purchase->notes)
        <div style="margin-top:5px;font-style:italic">Notes: {{ $purchase->notes }}</div>
        @endif
    </div>
    <div style="display:flex;gap:40px">
        <div class="sig-block">
            <div class="sig-line">Authorized by</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Supplier signature</div>
        </div>
    </div>
</div>

</body>
</html>
