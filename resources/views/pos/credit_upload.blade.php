{{-- Mobile page opened from the POS QR code to upload a photo of the signed credit bill. --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Upload signed copy</title>
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f1117;color:#e2e8f0;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:18px}
    .card{background:#161821;border:.5px solid #2a2d3a;border-radius:14px;padding:20px;width:100%;max-width:420px;margin-top:20px}
    h1{font-size:17px;font-weight:600;margin-bottom:4px}
    .sub{font-size:12px;color:#64748b;margin-bottom:16px}
    .meta{background:#0f1117;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px;margin-bottom:16px;font-size:13px}
    .meta .r{display:flex;justify-content:space-between;padding:2px 0}
    .meta .r span:first-child{color:#64748b}
    label{display:block;font-size:12px;color:#94a3b8;margin-bottom:6px;margin-top:14px}
    input[type=file],input[type=text]{width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:8px;color:#e2e8f0;font-size:15px;padding:12px;outline:none}
    input[type=text]{letter-spacing:1px}
    button{width:100%;height:50px;margin-top:20px;background:#312e81;border:.5px solid #534AB7;border-radius:9px;color:#fff;font-size:15px;font-weight:600;cursor:pointer}
    button:disabled{opacity:.55}
    .err{background:#3a1414;border:.5px solid #7f1d1d;color:#fca5a5;border-radius:8px;padding:10px 12px;font-size:13px;margin-top:14px}
    #status{font-size:13px;color:#a5b4fc;margin-top:12px;text-align:center;min-height:16px}
    .done{width:60px;height:60px;border-radius:50%;background:#14532d;color:#4ade80;font-size:34px;display:flex;align-items:center;justify-content:center;margin:6px auto 14px}
    .hint{font-size:11px;color:#475569;margin-top:6px}
</style>
</head>
<body>
<div class="card" id="card">
    <h1>{{ \App\Models\Setting::get('business_name', 'FreshMart') }}</h1>
    <div class="sub">Upload the signed credit bill</div>

    <div class="meta">
        <div class="r"><span>Invoice</span><span>{{ $sale->invoice_no }}</span></div>
        <div class="r"><span>Customer</span><span>{{ $sale->customer?->name ?? '—' }}</span></div>
        <div class="r"><span>On credit</span><span>Rs. {{ number_format($sale->balanceDue(), 2) }}</span></div>
    </div>

    @if($error)<div class="err">{{ $error }}</div>@endif

    <form id="f" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        <label>Photo of the signed bill</label>
        <input type="file" name="photo" id="photo" accept="image/*" capture="environment" required>

        <label>Security code or cashier password</label>
        <input type="text" name="secret" id="secret" inputmode="text" autocomplete="off" placeholder="6-digit code shown on the POS" required>
        <div class="hint">Enter the code on the POS screen, or the cashier's own login password.</div>

        <button type="submit" id="btn">Upload signed copy</button>
        <div id="status"></div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('f');
    const fileInput = document.getElementById('photo');
    const btn = document.getElementById('btn');
    const statusEl = document.getElementById('status');
    const token = document.querySelector('meta[name=csrf-token]').content;

    form.addEventListener('submit', async (e) => {
        if (!fileInput.files.length) return;          // let native "required" handle it
        e.preventDefault();
        btn.disabled = true;
        statusEl.textContent = 'Uploading…';
        try {
            const blob = await compress(fileInput.files[0]);
            const fd = new FormData();
            fd.append('photo', blob, 'signed.jpg');
            fd.append('secret', document.getElementById('secret').value);
            fd.append('_token', token);
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) { showDone(); }
            else { statusEl.textContent = data.message || 'Upload failed — check the code and try again.'; btn.disabled = false; }
        } catch (err) {
            statusEl.textContent = 'Upload failed. Please try again.';
            btn.disabled = false;
        }
    });

    // Shrink the photo in-browser so the upload is fast and stays within limits.
    async function compress(file) {
        try {
            const dataUrl = await new Promise((ok, no) => { const r = new FileReader(); r.onload = () => ok(r.result); r.onerror = no; r.readAsDataURL(file); });
            const img = await new Promise((ok, no) => { const i = new Image(); i.onload = () => ok(i); i.onerror = no; i.src = dataUrl; });
            const maxW = 1600;
            const scale = Math.min(1, maxW / (img.width || maxW));
            const w = Math.round((img.width || maxW) * scale), h = Math.round((img.height || maxW) * scale);
            const c = document.createElement('canvas'); c.width = w; c.height = h;
            c.getContext('2d').drawImage(img, 0, 0, w, h);
            const blob = await new Promise((ok) => c.toBlob(ok, 'image/jpeg', 0.82));
            return blob || file;
        } catch (e) {
            return file;   // fall back to the raw file if canvas isn't available
        }
    }

    function showDone() {
        document.getElementById('card').innerHTML =
            '<div class="done">&#10003;</div><h1 style="text-align:center">Signed copy received</h1>' +
            '<div class="sub" style="text-align:center;margin-top:6px">You can return to the counter — this page can be closed.</div>';
    }
})();
</script>
</body>
</html>
