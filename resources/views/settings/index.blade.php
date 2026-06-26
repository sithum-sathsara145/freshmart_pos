{{-- settings/index.blade.php --}}
@extends('layouts.app')
@section('title','Settings')
@section('page-title','Settings')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:200px 1fr;gap:14px;min-height:500px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 0;align-self:start">
    @foreach([
        ['business','ti-building-store','Business info'],
        ['branches','ti-map-pin','Branches'],
        ['counters','ti-device-desktop','Counters'],
        ['users','ti-users','Users & roles'],
        ['receipt','ti-receipt','Receipt setup'],
        ['tax','ti-percent','Tax settings'],
        ['hardware','ti-printer','Hardware'],
        ['backup','ti-database','Backup'],
    ] as [$key,$icon,$label])
    <div onclick="showSection('{{ $key }}')" id="nav-{{ $key }}"
        style="display:flex;align-items:center;gap:8px;padding:8px 14px;font-size:12px;color:#94a3b8;cursor:pointer;border-left:2px solid transparent;transition:all .12s"
        onmouseover="this.style.background='#1e2130';this.style.color='#e2e8f0'"
        onmouseout="if(currentSection!=='{{ $key }}'){this.style.background='';this.style.color='#94a3b8'}">
        <i class="ti {{ $icon }}" style="font-size:14px"></i>{{ $label }}
    </div>
    @endforeach
</div>

<form method="POST" action="{{ route('settings.save') }}">
@csrf
<div id="sec-business" class="settings-section">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Business information</div>
    @foreach([['business_name','Business name','text'],['address','Address','text'],['phone','Phone','text'],['email','Email','email']] as [$k,$l,$t])
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">{{ $l }}</label>
        <input type="{{ $t }}" name="{{ $k }}" value="{{ $settings[$k] ?? '' }}"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @endforeach
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Currency</label>
            <select name="currency" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="LKR" {{ ($settings['currency']??'LKR')==='LKR'?'selected':'' }}>LKR (Rs.)</option>
                <option value="USD" {{ ($settings['currency']??'')==='USD'?'selected':'' }}>USD ($)</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Date format</label>
            <select name="date_format" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
                <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
            </select>
        </div>
    </div>
</div>
</div>

<div id="sec-receipt" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Receipt customization</div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Receipt template</label>
        <select name="receipt_template" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
            <option value="thermal_58mm" {{ ($settings['receipt_template']??'thermal_58mm')==='thermal_58mm'?'selected':'' }}>Standard (58mm thermal)</option>
            <option value="thermal_80mm">Wide (80mm thermal)</option>
            <option value="a5">A5 invoice</option>
            <option value="a4">A4 invoice</option>
        </select>
    </div>
    <div style="margin-bottom:10px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Footer message</label>
        <input type="text" name="receipt_footer" value="{{ $settings['receipt_footer'] ?? 'Thank you! Visit again.' }}"
            style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
    @foreach([['Show logo','show_logo'],['Show customer name','show_customer'],['Show tax breakdown','show_tax'],['Show loyalty points','show_loyalty']] as [$l,$k])
    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#e2e8f0">{{ $l }}</span>
        <input type="checkbox" name="{{ $k }}" value="1" {{ ($settings[$k]??'1')==='1'?'checked':'' }} style="accent-color:#818cf8;width:16px;height:16px">
    </div>
    @endforeach
</div>
</div>

<div id="sec-branches" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px;display:flex;justify-content:space-between">
        <span>Branches</span>
        <a href="#" style="font-size:11px;padding:4px 10px;background:#312e81;color:#a5b4fc;border-radius:5px;text-decoration:none">+ Add branch</a>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a"><th style="padding:7px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Branch</th><th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">City</th><th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Status</th></tr></thead>
        <tbody>
        @foreach($branches as $b)
        <tr style="border-bottom:.5px solid #1a1d2a"><td style="padding:7px;color:#e2e8f0;font-weight:500">{{ $b->name }}</td><td style="padding:7px;color:#94a3b8">{{ $b->city }}</td><td style="padding:7px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#14532d;color:#4ade80">Active</span></td></tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<div id="sec-hardware" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Hardware configuration</div>
    @foreach([['Barcode scanner','barcode_scanner'],['Receipt printer','receipt_printer'],['Cash drawer','cash_drawer'],['Weighing scale integration','weighing_scale'],['Touch screen mode','touch_screen'],['Customer display','customer_display']] as [$l,$k])
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <div><div style="color:#e2e8f0">{{ $l }}</div></div>
        <input type="checkbox" name="{{ $k }}" value="1" {{ ($settings[$k]??'1')==='1'?'checked':'' }} style="accent-color:#818cf8;width:16px;height:16px">
    </div>
    @endforeach
</div>
</div>

<div id="sec-tax" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Tax settings</div>
    @foreach([['Enable tax','tax_enabled'],['Tax inclusive pricing','tax_inclusive'],['Show tax on receipt','show_tax_receipt']] as [$l,$k])
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#e2e8f0">{{ $l }}</span>
        <input type="checkbox" name="{{ $k }}" value="1" {{ ($settings[$k]??'0')==='1'?'checked':'' }} style="accent-color:#818cf8;width:16px;height:16px">
    </div>
    @endforeach
    <div style="margin-top:12px">
        <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Default tax rate %</label>
        <input type="number" name="default_tax_rate" value="{{ $settings['default_tax_rate'] ?? 0 }}" step="0.01"
            style="width:150px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none">
    </div>
</div>
</div>

<div id="sec-backup" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Backup & data</div>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#e2e8f0">Auto daily backup</span>
        <input type="checkbox" name="auto_backup" value="1" checked style="accent-color:#818cf8;width:16px;height:16px">
    </div>
    <div style="display:flex;gap:8px;margin-top:12px">
        <a href="#" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-download" style="font-size:12px"></i>Download backup</a>
        <a href="#" onclick="alert('Backup started!')" style="height:32px;padding:0 12px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-database" style="font-size:12px"></i>Backup now</a>
    </div>
    <div style="margin-top:10px;font-size:11px;color:#64748b">Last backup: {{ now()->format('d M Y, H:i') }}</div>
</div>
</div>

<div id="sec-users" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Users & roles</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:7px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Name</th>
            <th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Email</th>
            <th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Role</th>
            <th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @foreach($users as $u)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px;color:#e2e8f0;font-weight:500">{{ $u->name }}</td>
            <td style="padding:7px;color:#64748b">{{ $u->email }}</td>
            <td style="padding:7px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#312e81;color:#a5b4fc">{{ $u->getRoleNames()->first() ?? '—' }}</span></td>
            <td style="padding:7px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#14532d;color:#4ade80">Active</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<div id="sec-counters" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">POS Counters</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:7px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Counter</th>
            <th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Branch</th>
            <th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Cash balance</th>
            <th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @foreach($counters as $c)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px;color:#e2e8f0;font-weight:500">{{ $c->name }}</td>
            <td style="padding:7px;color:#94a3b8">{{ $c->branch?->name }}</td>
            <td style="padding:7px;color:#4ade80">Rs. {{ number_format($c->cash_balance) }}</td>
            <td style="padding:7px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $c->status==='open'?'#14532d':'#1e2130' }};color:{{ $c->status==='open'?'#4ade80':'#94a3b8' }}">{{ ucfirst($c->status) }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<div style="margin-top:14px">
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
        <i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save settings
    </button>
</div>
</form>
</div>

@push('scripts')
<script>
let currentSection = 'business';
function showSection(key) {
    document.querySelectorAll('.settings-section').forEach(s => s.style.display = 'none');
    document.getElementById('sec-' + key).style.display = 'block';
    document.querySelectorAll('[id^="nav-"]').forEach(n => {
        n.style.background = ''; n.style.color = '#94a3b8'; n.style.borderLeft = '2px solid transparent';
    });
    const nav = document.getElementById('nav-' + key);
    nav.style.background = '#1e2130'; nav.style.color = '#a5b4fc'; nav.style.borderLeft = '2px solid #818cf8';
    currentSection = key;
}
showSection('business');
</script>
@endpush
@endsection
