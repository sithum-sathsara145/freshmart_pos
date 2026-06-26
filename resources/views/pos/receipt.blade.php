{{-- pos/receipt.blade.php — Thermal receipt printed directly from POS --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>POS Receipt</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Courier New',Courier,monospace;font-size:11.5px;color:#000;background:#fff;width:300px;padding:10px 8px}
.center{text-align:center}
.right{text-align:right}
.bold{font-weight:700}
.dashed{border-top:1px dashed #888;margin:6px 0}
.solid{border-top:1px solid #000;margin:6px 0}
.row{display:flex;justify-content:space-between;align-items:center;padding:1.5px 0;line-height:1.4}
.logo-name{font-size:16px;font-weight:700;text-align:center;letter-spacing:1px;margin-bottom:1px}
.logo-sub{font-size:10px;text-align:center;color:#444;line-height:1.4}
.item-name{font-size:11px;color:#111;padding:1px 0}
.item-line{display:flex;justify-content:space-between;font-size:11px;color:#333;padding:0 0 3px 8px}
.total-row{font-size:13px;font-weight:700}
.footer-text{font-size:10px;text-align:center;color:#555;line-height:1.5;margin-top:3px}
.loyalty-box{border:1px dashed #888;border-radius:3px;padding:4px 8px;margin:5px 0;text-align:center;font-size:10px}
.barcode-placeholder{text-align:center;font-size:9px;font-family:monospace;letter-spacing:3px;margin-top:4px;color:#333}

/* Controls — hidden when printing */
.no-print{background:#f8f9fa;border:1px solid #ddd;border-radius:6px;padding:12px;margin-top:16px;text-align:center}
.no-print h3{font-size:12px;color:#374151;margin-bottom:8px;font-family:system-ui,sans-serif}
.no-print .btn-row{display:flex;gap:6px;justify-content:center}
.no-print button{padding:6px 16px;border-radius:5px;cursor:pointer;font-size:12px;font-family:system-ui,sans-serif;font-weight:500}
.btn-print{background:#312e81;color:#fff;border:none}
.btn-print:hover{background:#3c3a96}
.btn-close{background:#fff;color:#374151;border:1px solid #d1d5db}

@media print{
    .no-print{display:none!important}
    body{width:auto;padding:4px}
    @page{margin:0mm;size:58mm auto}
}
</style>
</head>
<body>

{{-- Header --}}
<div class="logo-name">{{ $settings['business_name'] ?? 'FreshMart' }}</div>
<div class="logo-sub">{{ $settings['address'] ?? 'No. 42, Main Street, Colombo 07' }}</div>
<div class="logo-sub">Tel: {{ $settings['phone'] ?? '011-2345678' }}</div>
@if(!empty($settings['email']))
<div class="logo-sub">{{ $settings['email'] }}</div>
@endif

<div class="dashed"></div>

{{-- Invoice meta --}}
<div class="row"><span>Invoice&nbsp;&nbsp;:</span><span class="bold">{{ $sale->invoice_no }}</span></div>
<div class="row"><span>Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span><span>{{ $sale->created_at->format('d/m/Y') }}</span></div>
<div class="row"><span>Time&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span><span>{{ $sale->created_at->format('H:i:s') }}</span></div>
<div class="row"><span>Cashier&nbsp; :</span><span>{{ $sale->user?->name ?? '—' }}</span></div>
<div class="row"><span>Counter&nbsp; :</span><span>{{ $sale->counter?->name ?? '—' }}</span></div>
@if($sale->customer)
<div class="row"><span>Customer :</span><span>{{ Str::limit($sale->customer->name, 16) }}</span></div>
@endif
<div class="row"><span>Payment&nbsp; :</span><span>{{ strtoupper($sale->payment_method) }}</span></div>

<div class="dashed"></div>

{{-- Items --}}
@php $totalItems = 0; @endphp
@foreach($sale->items as $item)
    @php $totalItems += $item->quantity; @endphp
    <div class="item-name">{{ Str::limit($item->product->name, 26) }}</div>
    <div class="item-line">
        <span>{{ number_format($item->quantity,0) }} x {{ number_format($item->unit_price) }}</span>
        <span class="bold">Rs. {{ number_format($item->subtotal) }}</span>
    </div>
@endforeach

<div class="dashed"></div>

{{-- Totals --}}
<div class="row"><span>Items sold ({{ $totalItems }})</span><span>Rs. {{ number_format($sale->subtotal) }}</span></div>

@if($sale->discount_amount > 0)
<div class="row"><span>Discount</span><span>- Rs. {{ number_format($sale->discount_amount) }}</span></div>
@endif

@if($sale->tax_amount > 0)
<div class="row"><span>Tax</span><span>Rs. {{ number_format($sale->tax_amount) }}</span></div>
@endif

<div class="solid"></div>
<div class="row total-row"><span>TOTAL</span><span>Rs. {{ number_format($sale->total) }}</span></div>
<div class="dashed"></div>

<div class="row"><span>Paid ({{ strtoupper($sale->payment_method) }})</span><span>Rs. {{ number_format($sale->paid_amount) }}</span></div>
@if($sale->change_amount > 0)
<div class="row bold"><span>Change</span><span>Rs. {{ number_format($sale->change_amount) }}</span></div>
@endif

{{-- Coupon --}}
@if(!empty($sale->coupon_code))
<div class="row"><span>Coupon ({{ $sale->coupon_code }})</span><span>- Rs. {{ number_format($sale->coupon_discount) }}</span></div>
@endif

<div class="dashed"></div>

{{-- Loyalty points --}}
@if($sale->customer && $sale->customer->loyalty_points > 0)
<div class="loyalty-box">
    ⭐ Loyalty points: <strong>{{ number_format($sale->customer->loyalty_points) }}</strong> pts
    @if($sale->loyalty_points_earned > 0)
    (+{{ $sale->loyalty_points_earned }} earned today)
    @endif
</div>
@endif

{{-- Barcode --}}
@if(!empty($barcode))
<div style="text-align:center;margin:5px 0">
    <img src="data:image/png;base64,{{ $barcode }}" style="height:35px;max-width:100%">
</div>
@endif
<div class="barcode-placeholder">{{ $sale->invoice_no }}</div>

{{-- Footer --}}
<div class="footer-text" style="margin-top:6px">
    {{ $settings['receipt_footer'] ?? 'Thank you for shopping with us!' }}<br>
    <span style="font-size:9px">Powered by FreshMart POS</span>
</div>

{{-- No-print controls --}}
<div class="no-print">
    <h3>Receipt Preview</h3>
    <div class="btn-row">
        <button class="btn-print" onclick="window.print()">🖨&nbsp;Print Receipt</button>
        <button class="btn-close" onclick="window.close()">✕ Close</button>
    </div>
    <div style="margin-top:8px;font-size:10px;color:#9ca3af;font-family:system-ui,sans-serif">
        Paper width: 58mm thermal
    </div>
</div>

<script>
    // Open the print dialog automatically so the cashier can print straight away
    window.addEventListener('load', () => setTimeout(() => window.print(), 250));
</script>

</body>
</html>
