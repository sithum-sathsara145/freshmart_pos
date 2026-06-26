{{-- suppliers/show.blade.php --}}
@extends('layouts.app')
@section('title','Supplier')
@section('page-title','Supplier — '.$supplier->name)
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('suppliers.index') }}" style="height:32px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrow-left" style="font-size:12px"></i>Back</a>
    <a href="{{ route('suppliers.edit',$supplier) }}" style="height:32px;padding:0 12px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i>Edit</a>
    <a href="{{ route('purchases.create') }}?supplier_id={{ $supplier->id }}" style="height:32px;padding:0 12px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-plus" style="font-size:12px"></i>New Purchase</a>
</div>
<div style="display:grid;grid-template-columns:280px 1fr;gap:12px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Supplier details</div>
    @foreach([['Name',$supplier->name,'#e2e8f0'],['Contact',$supplier->contact_person??'—','#94a3b8'],['Phone',$supplier->phone??'—','#94a3b8'],['Email',$supplier->email??'—','#94a3b8'],['City',$supplier->city??'—','#94a3b8'],['Total purchases','Rs. '.number_format($supplier->total_purchases),'#4ade80'],['Balance due','Rs. '.number_format($supplier->balance_due),$supplier->balance_due>0?'#f87171':'#4ade80']] as [$l,$v,$c])
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid #1a1d2a;font-size:12px">
        <span style="color:#64748b">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
    </div>
    @endforeach
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">Purchase history</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            <th style="padding:6px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">Bill #</th>
            <th style="padding:6px 10px;color:#64748b;font-weight:500;font-size:11px">Date</th>
            <th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Total</th>
            <th style="padding:6px 10px;text-align:right;color:#64748b;font-weight:500;font-size:11px">Balance</th>
            <th style="padding:6px 10px;text-align:center;color:#64748b;font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @forelse($supplier->purchases as $p)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:7px 10px;color:#60a5fa">{{ $p->bill_no }}</td>
            <td style="padding:7px 10px;color:#64748b">{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
            <td style="padding:7px 10px;text-align:right;color:#e2e8f0;font-weight:500">Rs. {{ number_format($p->total) }}</td>
            <td style="padding:7px 10px;text-align:right;color:{{ $p->balance_due>0?'#f87171':'#4ade80' }}">Rs. {{ number_format($p->balance_due) }}</td>
            <td style="padding:7px 10px;text-align:center"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->payment_status==='paid'?'#14532d':'#451a03' }};color:{{ $p->payment_status==='paid'?'#4ade80':'#fb923c' }}">{{ ucfirst($p->payment_status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="5" style="padding:20px;text-align:center;color:#4a5568">No purchases yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
</div>
@endsection
