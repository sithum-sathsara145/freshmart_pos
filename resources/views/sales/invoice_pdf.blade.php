{{-- sales/invoice_pdf.blade.php — A5 PDF via DomPDF --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111;background:#fff;padding:20px}
.header{display:flex;justify-content:space-between;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:14px}
.biz-name{font-size:18px;font-weight:700;color:#1a1a2e}
.biz-sub{font-size:10px;color:#666;margin-top:2px}
.inv-title{font-size:20px;font-weight:700;color:#312e81;text-align:right}
.inv-no{font-size:12px;color:#666;text-align:right}
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
.totals{float:right;width:200px}
.tot-row{display:flex;justify-content:space-between;font-size:11px;padding:3px 0;border-bottom:1px solid #eee}
.tot-row.grand{font-size:13px;font-weight:700;border-top:2px solid #312e81;border-bottom:none;padding-top:6px;color:#312e81}
.footer{margin-top:30px;border-top:1px solid #ddd;padding-top:10px;font-size:10px;color:#888;text-align:center}
.status-badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700;
    background:{{ ['paid'=>'#dcfce7','partial'=>'#fef3c7','returned'=>'#fee2e2'][$sale->status] ?? '#f1f5f9' }};
    color:{{ ['paid'=>'#166534','partial'=>'#854d0e','returned'=>'#991b1b'][$sale->status] ?? '#475569' }}}
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
        <div class="inv-title">INVOICE</div>
        <div class="inv-no"># {{ $sale->invoice_no }}</div>
        <div class="inv-no">{{ $sale->created_at->format('d M Y') }}</div>
        <div style="text-align:right;margin-top:5px"><span class="status-badge">{{ strtoupper($sale->status) }}</span></div>
    </div>
</div>

<div class="parties">
    <div class="party-box">
        <div class="party-lbl">From</div>
        <div class="party-name">{{ $settings['business_name'] ?? 'FreshMart Supermarket' }}</div>
        <div class="party-sub">{{ $sale->branch?->name }}</div>
    </div>
    <div class="party-box">
        <div class="party-lbl">Bill to</div>
        <div class="party-name">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
        @if($sale->customer)<div class="party-sub">{{ $sale->customer->phone }}</div>@endif
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th><th>Product</th><th>Unit</th><th class="right">Qty</th><th class="right">Unit price</th><th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $i => $item)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $item->product?->name ?? $item->name }}</td>
            <td>{{ $item->product?->unit ?? 'Item' }}</td>
            <td class="right">{{ $item->quantity }}</td>
            <td class="right">Rs. {{ number_format($item->unit_price) }}</td>
            <td class="right">Rs. {{ number_format($item->subtotal) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <div class="tot-row"><span>Subtotal</span><span>Rs. {{ number_format($sale->subtotal) }}</span></div>
    @if($sale->discount_amount > 0)
    <div class="tot-row"><span>Discount</span><span>- Rs. {{ number_format($sale->discount_amount) }}</span></div>
    @endif
    @if($sale->tax_amount > 0)
    <div class="tot-row"><span>Tax</span><span>Rs. {{ number_format($sale->tax_amount) }}</span></div>
    @endif
    <div class="tot-row grand"><span>TOTAL</span><span>Rs. {{ number_format($sale->total) }}</span></div>
    <div class="tot-row"><span>Paid</span><span>Rs. {{ number_format($sale->paid_amount) }}</span></div>
    @if($sale->balanceDue() > 0)
    <div class="tot-row" style="color:#991b1b"><span>Balance due</span><span>Rs. {{ number_format($sale->balanceDue()) }}</span></div>
    @endif
</div>

<div style="clear:both"></div>
<div class="footer">
    {{ $settings['receipt_footer'] ?? 'Thank you for your business! Visit us again.' }}<br>
    Cashier: {{ $sale->user?->name }} | Payment: {{ strtoupper($sale->payment_method) }}
</div>
</body>
</html>
