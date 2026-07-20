{{-- Shown after a no-JS (plain form) phone upload succeeds. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Signed copy received</title>
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f1117;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:18px}
    .card{background:#161821;border:.5px solid #2a2d3a;border-radius:14px;padding:26px 20px;width:100%;max-width:420px;text-align:center}
    .done{width:64px;height:64px;border-radius:50%;background:#14532d;color:#4ade80;font-size:36px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
    h1{font-size:18px;font-weight:600;margin-bottom:6px}
    p{font-size:13px;color:#64748b}
</style>
</head>
<body>
<div class="card">
    <div class="done">&#10003;</div>
    <h1>Signed copy received</h1>
    <p>Invoice {{ $sale->invoice_no }} — you can return to the counter and close this page.</p>
</div>
</body>
</html>
