{{-- barcodes/bulk_print.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Bulk barcodes</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#fff}
.copies-grid{display:flex;flex-wrap:wrap;gap:8px;padding:12px}
.label{border:1px solid #ddd;border-radius:4px;padding:10px 14px;text-align:center;min-width:180px}
.biz{font-size:9px;color:#666;margin-bottom:3px}
.prod-name{font-size:11px;font-weight:700;color:#111;margin-bottom:5px;line-height:1.2}
.barcode-img{max-width:160px;height:50px}
.barcode-num{font-size:9px;font-family:monospace;color:#333;letter-spacing:1.5px;margin-top:3px}
.price{font-size:13px;font-weight:700;color:#111;margin-top:4px}
.no-print button{margin:16px 6px;padding:8px 20px;border-radius:5px;cursor:pointer;font-size:12px}
.btn-print{background:#312e81;color:#fff;border:none}
.btn-close{background:#f1f5f9;color:#374151;border:1px solid #d1d5db}
@media print{.no-print{display:none}@page{margin:4mm;size:58mm auto}}
</style>
</head>
<body>

<div class="copies-grid">
    @foreach($barcodes as $b)
        @for($i = 0; $i < (int) $b['copies']; $i++)
        <div class="label">
            <div class="biz">{{ $settings['business_name'] ?? 'FreshMart' }}</div>
            <div class="prod-name">{{ Str::limit($b['product']->name, 24) }}</div>
            <img class="barcode-img" src="data:image/svg+xml;base64,{{ $b['barcode'] }}" alt="barcode">
            <div class="barcode-num">{{ $b['product']->barcode }}</div>
            <div class="price">Rs. {{ number_format($b['product']->sale_price) }}</div>
        </div>
        @endfor
    @endforeach
</div>

<div class="no-print" style="text-align:center;padding:10px">
    <button class="btn-print" onclick="window.print()">🖨 Print all</button>
    <button class="btn-close" onclick="window.close()">Close</button>
</div>

</body>
</html>
