{{-- quotations/pdf.blade.php — A4 PDF via DomPDF --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111;background:#fff;padding:28px}
.header{display:flex;justify-content:space-between;margin-bottom:22px;border-bottom:1px solid #ddd;padding-bottom:14px}
.biz-name{font-size:18px;font-weight:700;color:#1a1a2e}
.biz-sub{font-size:10px;color:#666;margin-top:2px}
.doc-title{font-size:20px;font-weight:700;color:#312e81;text-align:right}
.doc-no{font-size:12px;color:#666;text-align:right}
.parties{display:flex;justify-content:space-between;margin-bottom:16px}
.party-box{background:#f8f8fc;border-left:3px solid #312e81;padding:8px 12px;width:48%}
.party-lbl{font-size:9px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.party-name{font-size:12px;font-weight:600;color:#111}
.party-sub{font-size:10px;color:#555}
table{width:100%;border-collapse:collapse;margin-bottom:14px}
thead tr{background:#312e81;color:#fff}
thead th{padding:7px 10px;text-align:left;font-size:10px;font-weight:600}
thead th.right{text-align:right}
tbody tr{border-bottom:1px solid #eee}
tbody td{padding:6px 10px;font-size:11px}
tbody td.right{text-align:right}
tbody tr:nth-child(even){background:#f8f8fc}
.totals{float:right;width:220px}
.tot-row{display:flex;justify-content:space-between;font-size:11px;padding:3px 0;border-bottom:1px solid #eee}
.tot-row.grand{font-size:13px;font-weight:700;border-top:2px solid #312e81;border-bottom:none;padding-top:6px;color:#312e81}
.meta{clear:both;margin-top:18px;font-size:10px;color:#555}
.notes{margin-top:14px;background:#f8f8fc;border-left:3px solid #94a3b8;padding:8px 12px;font-size:10px;color:#444}
.footer{margin-top:34px;border-top:1px solid #ddd;padding-top:10px;font-size:10px;color:#888;text-align:center}
</style>
</head>
<body>
<div class="header">
    <div>
        <div class="biz-name">{{ $settings['business_name'] ?? 'FreshMart Supermarket' }}</div>
        <div class="biz-sub">{{ $settings['address'] ?? 'No. 42, Main Street, Colombo' }}</div>
        <div class="biz-sub">{{ $settings['phone'] ?? '011-2345678' }} | {{ $settings['email'] ?? '' }}</div>
    </div>
    <div>
        <div class="doc-title">QUOTATION</div>
        <div class="doc-no"># {{ $quotation->quote_no }}</div>
        <div class="doc-no">{{ $quotation->created_at->format('d M Y') }}</div>
        @if($quotation->valid_till)
        <div class="doc-no">Valid till: {{ \Carbon\Carbon::parse($quotation->valid_till)->format('d M Y') }}</div>
        @endif
    </div>
</div>

<div class="parties">
    <div class="party-box">
        <div class="party-lbl">From</div>
        <div class="party-name">{{ $settings['business_name'] ?? 'FreshMart Supermarket' }}</div>
        <div class="party-sub">{{ $quotation->branch?->name }}</div>
    </div>
    <div class="party-box">
        <div class="party-lbl">Quotation for</div>
        <div class="party-name">{{ $quotation->customer?->name ?? 'Walk-in Customer' }}</div>
        @if($quotation->customer)<div class="party-sub">{{ $quotation->customer->phone }}</div>@endif
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th><th>Product</th><th>Unit</th><th class="right">Qty</th><th class="right">Unit price</th><th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quotation->items as $i => $item)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $item->product?->name ?? 'Item' }}</td>
            <td>{{ $item->product?->unit ?? 'Item' }}</td>
            <td class="right">{{ $item->quantity }}</td>
            <td class="right">Rs. {{ number_format($item->unit_price) }}</td>
            <td class="right">Rs. {{ number_format($item->subtotal) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <div class="tot-row"><span>Subtotal</span><span>Rs. {{ number_format($quotation->subtotal) }}</span></div>
    @if($quotation->discount_amount > 0)
    <div class="tot-row"><span>Discount</span><span>- Rs. {{ number_format($quotation->discount_amount) }}</span></div>
    @endif
    @if($quotation->tax_amount > 0)
    <div class="tot-row"><span>Tax</span><span>Rs. {{ number_format($quotation->tax_amount) }}</span></div>
    @endif
    <div class="tot-row grand"><span>TOTAL</span><span>Rs. {{ number_format($quotation->total) }}</span></div>
</div>

<div style="clear:both"></div>

@if($quotation->notes)
<div class="notes"><strong>Notes:</strong> {{ $quotation->notes }}</div>
@endif

<div class="meta">This is a quotation, not a tax invoice. Prices are valid until the date shown above and are subject to stock availability.</div>

<div class="footer">
    {{ $settings['receipt_footer'] ?? 'Thank you for your interest! We look forward to serving you.' }}
</div>
</body>
</html>
