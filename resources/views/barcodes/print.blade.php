{{-- barcodes/print.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Barcode — {{ $product->name }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh}
.label{border:1px solid #ddd;border-radius:4px;padding:10px 14px;text-align:center;min-width:180px;display:inline-block}
.biz{font-size:9px;color:#666;margin-bottom:3px}
.prod-name{font-size:11px;font-weight:700;color:#111;margin-bottom:5px;line-height:1.2}
.barcode-img{max-width:160px;height:50px}
.barcode-num{font-size:9px;font-family:monospace;color:#333;letter-spacing:1.5px;margin-top:3px}
.price{font-size:13px;font-weight:700;color:#111;margin-top:4px}
.copies-grid{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;padding:20px}
.no-print button{margin:16px 6px;padding:8px 20px;border-radius:5px;cursor:pointer;font-size:12px}
.btn-print{background:#312e81;color:#fff;border:none}
.btn-close{background:#f1f5f9;color:#374151;border:1px solid #d1d5db}
@media print{.no-print{display:none}@page{margin:4mm;size:58mm auto}}
</style>
</head>
<body>

<div>
<div class="copies-grid" id="labels">
    {{-- Single label --}}
    <div class="label">
        <div class="biz">{{ $settings['business_name'] ?? 'FreshMart' }}</div>
        <div class="prod-name">{{ Str::limit($product->name, 24) }}</div>
        <img class="barcode-img" src="data:image/svg+xml;base64,{{ $barcode }}" alt="barcode">
        <div class="barcode-num">{{ $product->barcode }}</div>
        <div class="price">Rs. {{ number_format($product->sale_price) }}</div>
    </div>
</div>

<div class="no-print" style="text-align:center;padding:10px">
    <div style="margin-bottom:12px;font-size:13px;color:#374151">
        Copies:
        <input type="number" id="copies" value="1" min="1" max="100" style="width:60px;padding:4px;border:1px solid #ddd;border-radius:4px;text-align:center" onchange="renderCopies()">
    </div>
    <button class="btn-print" onclick="window.print()">🖨 Print Barcode(s)</button>
    <button class="btn-close" onclick="window.close()">Close</button>
</div>
</div>

<script>
const singleLabel = document.querySelector('.label').outerHTML;
function renderCopies() {
    const n = parseInt(document.getElementById('copies').value) || 1;
    const grid = document.getElementById('labels');
    grid.innerHTML = Array(n).fill(singleLabel).join('');
}
</script>
</body>
</html>
