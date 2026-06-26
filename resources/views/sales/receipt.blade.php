{{-- sales/receipt.blade.php — Thermal print (58mm/80mm) --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt {{ $sale->invoice_no }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Courier New',monospace;font-size:11px;color:#000;background:#fff;width:280px;padding:8px}
.center{text-align:center}
.right{text-align:right}
.bold{font-weight:700}
.line{border-top:1px dashed #666;margin:5px 0}
.row{display:flex;justify-content:space-between;padding:1px 0}
.logo{font-size:14px;font-weight:700;text-align:center;margin-bottom:2px}
.sub{font-size:10px;text-align:center;color:#444}
table{width:100%;font-size:10px;border-collapse:collapse}
td{padding:1px 2px}
.total-row{font-size:12px;font-weight:700}
@media print{
    body{width:auto}
    .no-print{display:none}
    @page{margin:0;size:58mm auto}
}
</style>
</head>
<body>
<div class="logo">{{ $settings['business_name'] ?? 'FreshMart' }}</div>
<div class="sub">{{ $settings['address'] ?? 'No. 42, Main Street, Colombo' }}</div>
<div class="sub">{{ $settings['phone'] ?? '011-2345678' }}</div>

<div class="line"></div>
<div class="row"><span>Invoice: <b>{{ $sale->invoice_no }}</b></span><span>{{ $sale->created_at->format('d/m/y H:i') }}</span></div>
@if($sale->customer)<div class="row"><span>Customer:</span><span>{{ $sale->customer->name }}</span></div>@endif
<div class="row"><span>Cashier:</span><span>{{ $sale->user?->name }}</span></div>
<div class="line"></div>

<table>
    <tbody>
        @foreach($sale->items as $item)
        <tr>
            <td colspan="3">{{ Str::limit($item->product->name, 22) }}</td>
        </tr>
        <tr>
            <td>{{ $item->quantity }} x {{ number_format($item->unit_price) }}</td>
            <td></td>
            <td class="right">Rs. {{ number_format($item->subtotal) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="line"></div>
<div class="row"><span>Subtotal</span><span>Rs. {{ number_format($sale->subtotal) }}</span></div>
@if($sale->discount_amount > 0)
<div class="row"><span>Discount</span><span>- Rs. {{ number_format($sale->discount_amount) }}</span></div>
@endif
@if($sale->tax_amount > 0)
<div class="row"><span>Tax</span><span>Rs. {{ number_format($sale->tax_amount) }}</span></div>
@endif
<div class="line"></div>
<div class="row total-row"><span>TOTAL</span><span>Rs. {{ number_format($sale->total) }}</span></div>
<div class="row"><span>Paid ({{ strtoupper($sale->payment_method) }})</span><span>Rs. {{ number_format($sale->paid_amount) }}</span></div>
@if($sale->change_amount > 0)
<div class="row"><span>Change</span><span>Rs. {{ number_format($sale->change_amount) }}</span></div>
@endif
<div class="line"></div>

@if($sale->customer && $sale->customer->loyalty_points > 0)
<div class="center" style="font-size:10px">Loyalty points: {{ $sale->customer->loyalty_points }} pts</div>
@endif

<div class="center" style="margin-top:5px;font-size:10px">{{ $settings['receipt_footer'] ?? 'Thank you! Visit again.' }}</div>

<div class="no-print" style="text-align:center;margin-top:16px">
    <button onclick="window.print()" style="padding:6px 20px;background:#312e81;color:#a5b4fc;border:none;border-radius:5px;cursor:pointer;font-size:12px">
        🖨 Print Receipt
    </button>
    <button onclick="window.close()" style="padding:6px 20px;background:#1e2130;color:#94a3b8;border:1px solid #2a2d3a;border-radius:5px;cursor:pointer;font-size:12px;margin-left:8px">
        Close
    </button>
</div>
</body>
</html>
