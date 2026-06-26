{{-- expenses/index.blade.php --}}
@extends('layouts.app')
@section('title','Expenses')
@section('page-title','Expenses')
@section('content')
<div style="padding:14px 16px">
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">This month</div><div style="font-size:18px;font-weight:500;color:#f87171">Rs. {{ number_format($totals['month']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Total all time</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">Rs. {{ number_format($totals['total']) }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">Categories</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">{{ $categories->count() }}</div></div>
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:10px 12px"><div style="font-size:10px;color:#64748b;margin-bottom:3px">This month records</div><div style="font-size:18px;font-weight:500;color:#e2e8f0">{{ $expenses->total() }}</div></div>
</div>
<div style="display:flex;gap:8px;margin-bottom:12px">
    <form method="GET" style="display:flex;gap:8px;flex:1">
        <input type="date" name="from_date" value="{{ request('from_date') }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
        <input type="date" name="to_date" value="{{ request('to_date') }}" style="height:34px;background:#161821;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;padding:0 8px;outline:none">
        <button type="submit" style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Filter</button>
    </form>
    <a href="{{ route('expenses.create') }}" style="height:34px;padding:0 14px;background:#312e81;color:#a5b4fc;border:.5px solid #534AB7;border-radius:6px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:5px;text-decoration:none">
        <i class="ti ti-plus" style="font-size:13px"></i>Add Expense
    </a>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;overflow:hidden">
<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead><tr style="border-bottom:.5px solid #2a2d3a">
        @foreach(['Date','Category','Description','Account','Amount','Receipt','Actions'] as $h)
        <th style="padding:9px 12px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
        @endforeach
    </tr></thead>
    <tbody>
    @forelse($expenses as $e)
    <tr style="border-bottom:.5px solid #1a1d2a">
        <td style="padding:9px 12px;color:#64748b">{{ \Carbon\Carbon::parse($e->expense_date)->format('d M Y') }}</td>
        <td style="padding:9px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#312e81;color:#a5b4fc">{{ $e->category?->name }}</span></td>
        <td style="padding:9px 12px;color:#e2e8f0">{{ $e->description }}</td>
        <td style="padding:9px 12px;color:#94a3b8">{{ $e->account?->name ?? '—' }}</td>
        <td style="padding:9px 12px;color:#f87171;font-weight:500">Rs. {{ number_format($e->amount) }}</td>
        <td style="padding:9px 12px">
            <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:{{ $e->receipt_image ? '#14532d' : '#1e2130' }};color:{{ $e->receipt_image ? '#4ade80' : '#94a3b8' }}">
                {{ $e->receipt_image ? 'Yes' : 'No' }}
            </span>
        </td>
        <td style="padding:9px 12px">
            <div style="display:flex;gap:3px">
                <a href="{{ route('expenses.edit',$e) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
                <form method="POST" action="{{ route('expenses.destroy',$e) }}" onsubmit="return confirm('Delete this expense?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#f87171;cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" style="padding:32px;text-align:center;color:#4a5568">No expenses found</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $expenses->links() }}</div>
</div>
@endsection
