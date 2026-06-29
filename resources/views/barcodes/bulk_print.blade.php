{{-- barcodes/bulk_print.blade.php — true-size label preview + print customization --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Barcode labels</title>
@php
    // Initial preset values fed to the JS controls below.
    $init = match($labelSize ?? 'a4') {
        'roll58' => ['preset' => 'roll58', 'pw' => 58, 'ph' => 0,   'cols' => 1, 'm' => 2,   'gap' => 2,   'bch' => 12, 'font' => 9],
        'roll40' => ['preset' => 'roll40', 'pw' => 40, 'ph' => 0,   'cols' => 1, 'm' => 1.5, 'gap' => 1.5, 'bch' => 10, 'font' => 8],
        default  => ['preset' => 'a4',     'pw' => 210,'ph' => 297, 'cols' => 4, 'm' => 8,   'gap' => 3,   'bch' => 14, 'font' => 10],
    };
    $totalLabels = collect($barcodes)->sum('copies');
@endphp
<style>
:root{
    --pw:{{ $init['pw'] }}mm; --ph:{{ $init['ph'] ? $init['ph'].'mm' : 'auto' }};
    --mt:{{ $init['m'] }}mm; --mr:{{ $init['m'] }}mm; --mb:{{ $init['m'] }}mm; --ml:{{ $init['m'] }}mm;
    --cols:{{ $init['cols'] }}; --gap:{{ $init['gap'] }}mm; --bch:{{ $init['bch'] }}mm; --font:{{ $init['font'] }}px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#e5e7eb;color:#1e293b}
.wrap{display:flex;height:100vh}

/* Controls panel */
.controls{width:248px;flex-shrink:0;background:#fff;border-right:1px solid #d1d5db;overflow-y:auto;padding:14px}
.controls h2{font-size:13px;margin-bottom:2px}
.controls .sub{font-size:11px;color:#64748b;margin-bottom:12px}
.grp{border-top:1px solid #eef0f3;padding:11px 0}
.grp-title{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:8px}
.row{display:flex;gap:7px;margin-bottom:7px}
.fld{flex:1}
.fld label{display:block;font-size:10px;color:#64748b;margin-bottom:3px}
.fld input,.fld select{width:100%;border:1px solid #d1d5db;border-radius:5px;padding:5px 7px;font-size:12px;outline:none}
.fld input:focus,.fld select:focus{border-color:#6366f1}
.chk{display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:6px;cursor:pointer}
.btns{position:sticky;bottom:0;background:#fff;padding-top:12px;margin-top:6px}
.btn{width:100%;padding:9px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;border:none;margin-bottom:7px}
.btn-print{background:#312e81;color:#fff}
.btn-close{background:#f1f5f9;color:#374151;border:1px solid #d1d5db}
.count{font-size:11px;color:#475569;text-align:center;margin-bottom:8px}

/* Preview area — rendered at real mm size so it matches the paper */
.preview{flex:1;overflow:auto;padding:22px;display:flex;justify-content:center;align-items:flex-start}
.page{width:var(--pw);min-height:var(--ph);padding:var(--mt) var(--mr) var(--mb) var(--ml);
      background:#fff;box-shadow:0 3px 16px rgba(0,0,0,.22)}
.content{outline:1px dashed #cbd5e1}      /* printable-area guide */
.grid{display:grid;grid-template-columns:repeat(var(--cols),1fr);gap:var(--gap)}
.label{border:1px solid #e2e8f0;border-radius:3px;padding:4px 5px;text-align:center;break-inside:avoid;overflow:hidden}
.biz{font-size:calc(var(--font) * .8);color:#666;line-height:1.1}
.prod-name{font-size:var(--font);font-weight:700;color:#111;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:1px 0}
.barcode-img{width:100%;height:var(--bch);display:block}
.barcode-num{font-size:calc(var(--font) * .8);font-family:monospace;color:#333;letter-spacing:1px;margin-top:1px}
.price{font-size:calc(var(--font) * 1.15);font-weight:700;color:#111;margin-top:1px}
.hide-biz .biz{display:none}.hide-name .prod-name{display:none}
.hide-num .barcode-num{display:none}.hide-price .price{display:none}

@media print{
    body{background:#fff}
    .wrap{display:block;height:auto}
    .controls{display:none}
    .preview{padding:0;display:block;overflow:visible}
    .page{width:auto;min-height:0;padding:0;box-shadow:none;margin:0}
    .content{outline:none}
}
</style>
<style id="pagestyle"></style>
</head>
<body>
<div class="wrap">

    {{-- ── Controls ─────────────────────────────────────── --}}
    <div class="controls">
        <h2>Label setup</h2>
        <div class="sub">Preview is shown at real size.</div>

        <div class="grp">
            <div class="grp-title">Paper</div>
            <div class="fld" style="margin-bottom:7px">
                <label>Preset</label>
                <select id="preset" onchange="setPreset(this.value)">
                    <option value="a4">A4 sheet</option>
                    <option value="roll58">Thermal roll 58mm</option>
                    <option value="roll40">Thermal roll 40mm</option>
                    <option value="custom">Custom…</option>
                </select>
            </div>
            <div class="row">
                <div class="fld"><label>Width (mm)</label><input type="number" id="pw" min="20" max="400" step="1" oninput="customize()"></div>
                <div class="fld"><label>Height (mm, 0=roll)</label><input type="number" id="ph" min="0" max="600" step="1" oninput="customize()"></div>
            </div>
        </div>

        <div class="grp">
            <div class="grp-title">Margins (mm)</div>
            <div class="row">
                <div class="fld"><label>Top</label><input type="number" id="mt" min="0" step=".5" oninput="apply()"></div>
                <div class="fld"><label>Right</label><input type="number" id="mr" min="0" step=".5" oninput="apply()"></div>
            </div>
            <div class="row">
                <div class="fld"><label>Bottom</label><input type="number" id="mb" min="0" step=".5" oninput="apply()"></div>
                <div class="fld"><label>Left</label><input type="number" id="ml" min="0" step=".5" oninput="apply()"></div>
            </div>
        </div>

        <div class="grp">
            <div class="grp-title">Layout</div>
            <div class="row">
                <div class="fld"><label>Columns</label><input type="number" id="cols" min="1" max="12" step="1" oninput="apply()"></div>
                <div class="fld"><label>Gap (mm)</label><input type="number" id="gap" min="0" step=".5" oninput="apply()"></div>
            </div>
            <div class="row">
                <div class="fld"><label>Barcode height (mm)</label><input type="number" id="bch" min="6" max="40" step="1" oninput="apply()"></div>
                <div class="fld"><label>Font (px)</label><input type="number" id="font" min="6" max="20" step="1" oninput="apply()"></div>
            </div>
        </div>

        <div class="grp">
            <div class="grp-title">Show on label</div>
            <label class="chk"><input type="checkbox" id="s-biz"  onchange="apply()">Business name</label>
            <label class="chk"><input type="checkbox" id="s-name" {{ ($showName ?? true) ? 'checked' : '' }} onchange="apply()">Product name</label>
            <label class="chk"><input type="checkbox" id="s-num"  checked onchange="apply()">Barcode number</label>
            <label class="chk"><input type="checkbox" id="s-price" {{ ($showPrice ?? true) ? 'checked' : '' }} onchange="apply()">Price</label>
        </div>

        <div class="btns">
            <div class="count"><b>{{ $totalLabels }}</b> labels · {{ count($barcodes) }} products</div>
            <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
            <button class="btn btn-close" onclick="window.close()">Close</button>
        </div>
    </div>

    {{-- ── Live preview ─────────────────────────────────── --}}
    <div class="preview">
        <div class="page">
            <div class="content">
                <div class="grid">
                    @foreach($barcodes as $b)
                        @for($i = 0; $i < (int) $b['copies']; $i++)
                        <div class="label">
                            <div class="biz">{{ $settings['business_name'] ?? 'FreshMart' }}</div>
                            <div class="prod-name">{{ \Illuminate\Support\Str::limit($b['product']->name, 28) }}</div>
                            <img class="barcode-img" src="data:image/svg+xml;base64,{{ $b['barcode'] }}" alt="barcode">
                            <div class="barcode-num">{{ $b['product']->barcode }}</div>
                            <div class="price">Rs. {{ number_format($b['product']->sale_price) }}</div>
                        </div>
                        @endfor
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const PRESETS = {
    a4:     { pw:210, ph:297, cols:4, m:8,   gap:3,   bch:14, font:10 },
    roll58: { pw:58,  ph:0,   cols:1, m:2,   gap:2,   bch:12, font:9  },
    roll40: { pw:40,  ph:0,   cols:1, m:1.5, gap:1.5, bch:10, font:8  },
};
const $ = id => document.getElementById(id);

function setPreset(name) {
    if (name === 'custom') { apply(); return; }
    const p = PRESETS[name]; if (!p) return;
    $('pw').value = p.pw; $('ph').value = p.ph; $('cols').value = p.cols;
    $('mt').value = $('mr').value = $('mb').value = $('ml').value = p.m;
    $('gap').value = p.gap; $('bch').value = p.bch; $('font').value = p.font;
    apply();
}

// Editing a paper dimension flips the preset to "custom".
function customize() { $('preset').value = 'custom'; apply(); }

function apply() {
    const r = document.documentElement.style;
    const ph = parseFloat($('ph').value) || 0;
    r.setProperty('--pw', (parseFloat($('pw').value) || 210) + 'mm');
    r.setProperty('--ph', ph ? ph + 'mm' : 'auto');
    r.setProperty('--mt', (parseFloat($('mt').value) || 0) + 'mm');
    r.setProperty('--mr', (parseFloat($('mr').value) || 0) + 'mm');
    r.setProperty('--mb', (parseFloat($('mb').value) || 0) + 'mm');
    r.setProperty('--ml', (parseFloat($('ml').value) || 0) + 'mm');
    r.setProperty('--cols', Math.max(1, parseInt($('cols').value) || 1));
    r.setProperty('--gap', (parseFloat($('gap').value) || 0) + 'mm');
    r.setProperty('--bch', (parseFloat($('bch').value) || 12) + 'mm');
    r.setProperty('--font', (parseFloat($('font').value) || 10) + 'px');

    document.body.classList.toggle('hide-biz',  !$('s-biz').checked);
    document.body.classList.toggle('hide-name', !$('s-name').checked);
    document.body.classList.toggle('hide-num',  !$('s-num').checked);
    document.body.classList.toggle('hide-price',!$('s-price').checked);

    // Drive the real printed page size + margins.
    const size = ph ? (parseFloat($('pw').value) + 'mm ' + ph + 'mm') : (parseFloat($('pw').value) + 'mm auto');
    const margin = [$('mt').value, $('mr').value, $('mb').value, $('ml').value].map(v => (parseFloat(v) || 0) + 'mm').join(' ');
    $('pagestyle').textContent = '@media print{@page{size:' + size + ';margin:' + margin + '}}';
}

// Initialise from the preset the picker chose.
$('preset').value = @json($init['preset']);
setPreset(@json($init['preset']));
</script>
</body>
</html>
