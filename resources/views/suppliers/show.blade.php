{{-- suppliers/show.blade.php --}}
@extends('layouts.app')
@section('title','Supplier')
@section('page-title','Supplier — '.$supplier->name)
@section('content')
<div style="padding:14px 16px">
<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('suppliers.index') }}" style="height:32px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-arrow-left" style="font-size:12px"></i>Back</a>
    <a href="{{ route('suppliers.edit',$supplier) }}" style="height:32px;padding:0 12px;background:var(--primary-soft);color:var(--primary-text);border:.5px solid var(--primary-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i>Edit</a>
    <a href="{{ route('purchases.create') }}?supplier_id={{ $supplier->id }}" style="height:32px;padding:0 12px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none"><i class="ti ti-plus" style="font-size:12px"></i>New Purchase</a>
</div>
<div style="display:grid;grid-template-columns:280px 1fr;gap:12px">
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Supplier details</div>
    @foreach([['Name',$supplier->name,'var(--text)'],['Contact',$supplier->contact_person??'—','var(--text-2)'],['Phone',$supplier->phone??'—','var(--text-2)'],['Email',$supplier->email??'—','var(--text-2)'],['City',$supplier->city??'—','var(--text-2)'],['Total purchases','Rs. '.number_format($supplier->total_purchases),'var(--success)'],['Balance due','Rs. '.number_format($supplier->balance_due),$supplier->balance_due>0?'var(--danger)':'var(--success)']] as [$l,$v,$c])
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:.5px solid var(--surface-3);font-size:12px">
        <span style="color:var(--text-3)">{{ $l }}</span><span style="color:{{ $c }};font-weight:500">{{ $v }}</span>
    </div>
    @endforeach
</div>
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Purchase history</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid var(--border)">
            <th style="padding:6px 10px;text-align:left;color:var(--text-3);font-weight:500;font-size:11px">Bill #</th>
            <th style="padding:6px 10px;color:var(--text-3);font-weight:500;font-size:11px">Date</th>
            <th style="padding:6px 10px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Total</th>
            <th style="padding:6px 10px;text-align:right;color:var(--text-3);font-weight:500;font-size:11px">Balance</th>
            <th style="padding:6px 10px;text-align:center;color:var(--text-3);font-weight:500;font-size:11px">Status</th>
        </tr></thead>
        <tbody>
        @forelse($supplier->purchases as $p)
        <tr style="border-bottom:.5px solid var(--surface-3)">
            <td style="padding:7px 10px;color:var(--info)">{{ $p->bill_no }}</td>
            <td style="padding:7px 10px;color:var(--text-3)">{{ \Carbon\Carbon::parse($p->purchase_date)->format('d M Y') }}</td>
            <td style="padding:7px 10px;text-align:right;color:var(--text);font-weight:500">Rs. {{ number_format($p->total) }}</td>
            <td style="padding:7px 10px;text-align:right;color:{{ $p->balance_due>0?'var(--danger)':'var(--success)' }}">Rs. {{ number_format($p->balance_due) }}</td>
            <td style="padding:7px 10px;text-align:center"><span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $p->payment_status==='paid'?'var(--success-soft)':'var(--warning-soft)' }};color:{{ $p->payment_status==='paid'?'var(--success)':'var(--warning)' }}">{{ ucfirst($p->payment_status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="5" style="padding:20px;text-align:center;color:var(--text-4)">No purchases yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
</div>
@endsection
