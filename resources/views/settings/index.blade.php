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
        ['scale','ti-scale','Scale barcodes'],
        ['apikeys','ti-key','API keys'],
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

<div style="min-width:0">
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

<div id="sec-scale" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:4px">Scale barcodes (weighed items)</div>
    <div style="font-size:11px;color:#64748b;margin-bottom:14px">
        For items sold by weight, where the scale prints an embedded EAN-13 barcode (GS1 prefix “2”).
        Ordinary product barcodes are never affected. Defaults match the common CAS / Essae scales used in Sri Lanka —
        change them only if your scale is programmed differently.
    </div>

    @php
        $scl = fn($k,$d) => $settings[$k] ?? $d;
        $sinp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none';
        $slbl = 'display:block;font-size:11px;color:#64748b;margin-bottom:4px';
    @endphp

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
            <label style="{{ $slbl }}">Enable scale barcodes</label>
            <select name="scale_enabled" style="{{ $sinp }}">
                <option value="0" {{ $scl('scale_enabled','0')==='0'?'selected':'' }}>Disabled</option>
                <option value="1" {{ $scl('scale_enabled','0')==='1'?'selected':'' }}>Enabled</option>
            </select>
        </div>
        <div>
            <label style="{{ $slbl }}">Embedded value</label>
            <select name="scale_embed" style="{{ $sinp }}">
                <option value="price"  {{ $scl('scale_embed','price')==='price'?'selected':'' }}>Price (total)</option>
                <option value="weight" {{ $scl('scale_embed','price')==='weight'?'selected':'' }}>Weight</option>
            </select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:10px">
        <div>
            <label style="{{ $slbl }}">Prefix</label>
            <input type="text" name="scale_prefix" value="{{ $scl('scale_prefix','2') }}" style="{{ $sinp }}">
        </div>
        <div>
            <label style="{{ $slbl }}">Total length</label>
            <input type="number" name="scale_total_length" value="{{ $scl('scale_total_length','13') }}" min="6" max="14" style="{{ $sinp }}">
        </div>
        <div>
            <label style="{{ $slbl }}">PLU digits</label>
            <input type="number" name="scale_plu_length" value="{{ $scl('scale_plu_length','5') }}" min="1" max="8" style="{{ $sinp }}">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
        <div>
            <label style="{{ $slbl }}">Value digits</label>
            <input type="number" name="scale_value_length" value="{{ $scl('scale_value_length','5') }}" min="1" max="8" style="{{ $sinp }}">
        </div>
        <div>
            <label style="{{ $slbl }}">Value divisor</label>
            <input type="number" name="scale_value_divisor" value="{{ $scl('scale_value_divisor','100') }}" min="1" step="1" style="{{ $sinp }}">
        </div>
    </div>

    <div style="font-size:11px;color:#64748b;margin-top:10px;line-height:1.5">
        <b style="color:#94a3b8">Divisor</b> — the embedded number is divided by this to get the real amount.
        Price with cents → <b>100</b>; price in whole rupees → <b>1</b>; weight in grams → <b>1000</b>.<br>
        <b style="color:#94a3b8">PLU</b> links to a product's <i>Scale PLU</i> field (set on the product's edit page).
    </div>

    <div style="border-top:.5px solid #2a2d3a;margin-top:14px;padding-top:14px">
        <div style="font-size:12px;color:#e2e8f0;font-weight:500;margin-bottom:2px">Internal item barcodes</div>
        <div style="font-size:11px;color:#64748b;margin-bottom:10px;line-height:1.5">
            Auto-generated for store-made items with no manufacturer barcode (just leave a product's barcode blank).
            Uses the GS1 in-store range, so it never clashes with real product barcodes. Keep it within
            <b style="color:#94a3b8">20–29</b> and different from the scale prefix above.
        </div>
        <div style="max-width:200px">
            <label style="{{ $slbl }}">Internal barcode prefix</label>
            <input type="text" name="internal_barcode_prefix" value="{{ $scl('internal_barcode_prefix','21') }}" inputmode="numeric" style="{{ $sinp }}">
        </div>
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

<div id="sec-users" class="settings-section" style="display:none" x-data="usersTab()">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-size:12px;font-weight:500;color:#94a3b8">Users & roles</div>
        <div style="display:flex;gap:8px">
            @can('viewAny', App\Models\Role::class)
            <a href="{{ route('roles.index') }}" style="height:28px;padding:0 10px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#94a3b8;font-size:11px;display:flex;align-items:center;gap:4px;text-decoration:none">
                <i class="ti ti-shield-lock" style="font-size:12px"></i>Roles & permissions
            </a>
            @endcan
            @can('create', App\Models\User::class)
            <button type="button" @click="openCreate()" style="height:28px;padding:0 10px;background:#312e81;border:.5px solid #534AB7;border-radius:5px;color:#a5b4fc;font-size:11px;cursor:pointer;display:flex;align-items:center;gap:4px">
                <i class="ti ti-plus" style="font-size:12px"></i>Add user
            </button>
            @endcan
        </div>
    </div>

    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:7px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Name</th>
            <th style="padding:7px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Email</th>
            <th style="padding:7px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Role</th>
            <th style="padding:7px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Branch</th>
            <th style="padding:7px;color:#64748b;font-weight:500;font-size:11px">Status</th>
            <th style="padding:7px;width:70px"></th>
        </tr></thead>
        <tbody>
        @forelse($users as $u)
        @php
            $role = $u->roles->first();
            $payload = [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'phone' => $u->phone, 'branch_id' => $u->branch_id,
                'counter_id' => $u->counter_id, 'role' => $role?->name,
                'status' => $u->status,
            ];
        @endphp
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px;color:#e2e8f0;font-weight:500">
                {{ $u->name }}
                @if($u->is(auth()->user()))<span style="font-size:9px;color:#64748b"> (you)</span>@endif
            </td>
            <td style="padding:7px;color:#64748b">{{ $u->email }}</td>
            <td style="padding:7px"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#312e81;color:#a5b4fc">{{ $role?->displayName() ?? '—' }}</span></td>
            <td style="padding:7px;color:#64748b">{{ $u->branch?->name ?? '—' }}</td>
            <td style="padding:7px;text-align:center">
                <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $u->isActive() ? '#14532d' : '#7f1d1d' }};color:{{ $u->isActive() ? '#4ade80' : '#fca5a5' }}">
                    {{ $u->isActive() ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td style="padding:7px">
                @can('update', $u)
                <div style="display:flex;gap:5px;justify-content:flex-end">
                    <button type="button" title="Edit" @click="openEdit({{ Js::from($payload) }})"
                        style="width:24px;height:24px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#60a5fa;cursor:pointer"><i class="ti ti-pencil" style="font-size:12px"></i></button>
                    <button type="button" title="Delete" @click="confirmDelete({{ $u->id }}, @js($u->name))"
                        style="width:24px;height:24px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;color:#f87171;cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                </div>
                @endcan
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="padding:20px;text-align:center;color:#4a5568;font-size:11px">No users.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="font-size:10px;color:#4a5568;margin-top:9px">
        You can only edit accounts ranked at or below your own role.
    </div>
</div>

{{-- Add / edit user --}}
<template x-teleport="body">
<div x-show="showModal" x-cloak @keydown.escape.window="showModal=false" @click.self="showModal=false"
     style="position:fixed;inset:0;background:rgba(8,9,13,.7);display:flex;align-items:center;justify-content:center;z-index:60">
    <form :action="form.id ? '{{ url('users') }}/' + form.id : '{{ route('users.store') }}'" method="POST"
          style="background:#161821;border:.5px solid #2a2d3a;border-radius:10px;padding:18px;width:420px;max-height:90vh;overflow-y:auto">
        @csrf
        <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
        <div style="font-size:13px;font-weight:600;color:#e2e8f0;margin-bottom:2px" x-text="form.id ? 'Edit user' : 'Add user'"></div>
        <div style="font-size:11px;color:#64748b;margin-bottom:14px">Roles you can hand out are limited to your own rank and below.</div>

        @php $ui = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:8px 10px;outline:none'; @endphp
        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Full name *</label>
            <input name="name" x-model="form.name" required style="{{ $ui }}">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:9px">
            <div>
                <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Email *</label>
                <input name="email" type="email" x-model="form.email" required style="{{ $ui }}">
            </div>
            <div>
                <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Phone</label>
                <input name="phone" x-model="form.phone" style="{{ $ui }}">
            </div>
        </div>
        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">
                Password <span x-show="form.id" style="color:#4a5568">— leave blank to keep current</span>
                <span x-show="!form.id">*</span>
            </label>
            <input name="password" type="password" autocomplete="new-password" :required="!form.id" style="{{ $ui }}">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:9px">
            <div>
                <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Role *</label>
                <select name="role" x-model="form.role" required style="{{ $ui }}">
                    <option value="">— Select —</option>
                    @foreach($assignableRoles as $r)
                    <option value="{{ $r->name }}">{{ $r->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Status</label>
                <select name="status" x-model="form.status" style="{{ $ui }}">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive — cannot log in</option>
                </select>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
            <div>
                <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Branch</label>
                @if(auth()->user()->seesAllBranches())
                <select name="branch_id" x-model="form.branch_id" style="{{ $ui }}">
                    <option value="">— None —</option>
                    @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </select>
                @else
                <input disabled value="{{ auth()->user()->branch?->name ?? '—' }}" style="{{ $ui }};opacity:.5">
                @endif
            </div>
            <div>
                <label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Counter</label>
                <select name="counter_id" x-model="form.counter_id" style="{{ $ui }}">
                    <option value="">— None —</option>
                    @foreach($counters as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->branch?->name }})</option>@endforeach
                </select>
            </div>
        </div>

        <div style="display:flex;gap:8px">
            <button type="button" @click="showModal=false" style="flex:1;height:36px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Cancel</button>
            <button type="submit" style="flex:1;height:36px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer">Save user</button>
        </div>
    </form>
</div>
</template>

{{-- Delete --}}
<form x-ref="deleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
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

<div id="main-save-bar" style="margin-top:14px">
    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
        <i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save settings
    </button>
</div>
</form>

{{-- API keys — separate form posting to the encrypted endpoint --}}
<form method="POST" action="{{ route('settings.api-keys.save') }}">
@csrf
<div id="sec-apikeys" class="settings-section" style="display:none">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:4px">API keys</div>
    <div style="font-size:11px;color:#64748b;margin-bottom:14px;display:flex;align-items:center;gap:6px">
        <i class="ti ti-lock" style="font-size:13px;color:#4ade80"></i>
        Secret values are encrypted before storage and never shown again — leave a field blank to keep its current value.
    </div>

    @php $inp = 'width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none'; @endphp
    @foreach($apiCredentials as $groupKey => $group)
    <div style="border:.5px solid #2a2d3a;border-radius:8px;padding:12px;margin-bottom:12px">
        <div style="font-size:12px;color:#e2e8f0;font-weight:500;margin-bottom:2px">{{ $group['label'] }}</div>
        @if(!empty($group['description']))
        <div style="font-size:11px;color:#64748b;margin-bottom:10px">{{ $group['description'] }}</div>
        @endif

        @foreach($group['fields'] as $fkey => $f)
        @php
            $isSecret = !empty($f['secret']);
            $state    = $apiKeyState[$fkey] ?? [];
            $isSet    = $isSecret ? ($state['set'] ?? false) : filled($state['value'] ?? '');
        @endphp
        <div style="margin-bottom:10px">
            <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:#64748b;margin-bottom:4px">
                {{ $f['label'] }}
                @if($isSecret && $isSet)<span style="font-size:9px;padding:1px 6px;border-radius:8px;background:#14532d;color:#4ade80">Saved</span>@endif
            </label>
            @if($isSecret)
            <input type="password" name="{{ $fkey }}" value="" autocomplete="new-password"
                placeholder="{{ $isSet ? '•••••••• (leave blank to keep)' : ($f['placeholder'] ?? 'Enter value') }}"
                style="{{ $inp }}">
            @if($isSet)
            <label style="display:flex;align-items:center;gap:5px;font-size:10px;color:#94a3b8;margin-top:5px;cursor:pointer">
                <input type="checkbox" name="{{ $fkey }}_clear" value="1" style="accent-color:#f87171;width:13px;height:13px"> Clear saved value
            </label>
            @endif
            @else
            <input type="text" name="{{ $fkey }}" value="{{ $state['value'] ?? '' }}"
                placeholder="{{ $f['placeholder'] ?? '' }}" style="{{ $inp }}">
            @endif
        </div>
        @endforeach
    </div>
    @endforeach

    <button type="submit" style="height:36px;padding:0 20px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer">
        <i class="ti ti-check" style="font-size:13px;margin-right:4px"></i>Save API keys
    </button>
</div>
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
    // The shared "Save settings" button belongs to the main form — hide it on the API keys tab.
    const mainSave = document.getElementById('main-save-bar');
    if (mainSave) mainSave.style.display = (key === 'apikeys') ? 'none' : '';
    currentSection = key;
}
const initialSection = (location.hash || '').replace('#', '');
showSection(document.getElementById('sec-' + initialSection) ? initialSection : 'business');

// ── Users tab: add / edit / delete ──────────────────────────────────────
function usersTab() {
    const blank = { id: null, name: '', email: '', phone: '', role: '', status: 'active', branch_id: '', counter_id: '' };
    return {
        showModal: false,
        form: { ...blank },

        openCreate() {
            this.form = { ...blank };
            this.showModal = true;
        },
        openEdit(user) {
            // Normalise nulls so the selects bind cleanly.
            this.form = {
                id: user.id,
                name: user.name ?? '',
                email: user.email ?? '',
                phone: user.phone ?? '',
                role: user.role ?? '',
                status: user.status ?? 'active',
                branch_id: user.branch_id ?? '',
                counter_id: user.counter_id ?? '',
            };
            this.showModal = true;
        },
        confirmDelete(id, name) {
            if (!confirm(`Delete "${name}"? If they already have sales or staff records they'll be deactivated instead.`)) return;
            const f = this.$refs.deleteForm;
            f.action = '{{ url('users') }}/' + id;
            f.submit();
        },
    };
}
</script>
@endpush
@endsection
