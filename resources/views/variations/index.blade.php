{{-- variations/index.blade.php --}}
@extends('layouts.app')
@section('title','Variations')
@section('page-title','Product Variations')
@section('content')
<div style="padding:14px 16px;display:grid;grid-template-columns:320px 1fr;gap:14px">
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px;align-self:start">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Add variation</div>
    <form method="POST" action="{{ route('variations.store') }}">
    @csrf
    <div style="margin-bottom:10px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Variation name *</label><input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Size" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
    <div style="margin-bottom:12px"><label style="display:block;font-size:11px;color:#64748b;margin-bottom:4px">Values <span style="color:#475569">(comma separated)</span></label><input type="text" name="values" value="{{ old('values') }}" placeholder="e.g. Small, Medium, Large" style="width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:7px 10px;outline:none"></div>
    <button type="submit" style="width:100%;height:36px;background:#14532d;color:#4ade80;border:.5px solid #166534;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-plus" style="font-size:13px;margin-right:4px"></i>Add Variation</button>
    @error('name')<div style="color:#f87171;font-size:11px;margin-top:8px">{{ $message }}</div>@enderror
    </form>
</div>
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:10px">All variations</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead><tr style="border-bottom:.5px solid #2a2d3a">
            @foreach(['Variation','Values','Actions'] as $h)
            <th style="padding:7px 10px;text-align:left;color:#64748b;font-weight:500;font-size:11px">{{ $h }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @forelse($types as $t)
        <tr style="border-bottom:.5px solid #1a1d2a">
            <td style="padding:9px 10px;color:#e2e8f0;font-weight:500;vertical-align:top">{{ $t->name }}</td>
            <td style="padding:9px 10px">
                <div style="display:flex;flex-wrap:wrap;gap:4px">
                    @forelse($t->values as $v)
                    <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:#1e2130;color:#94a3b8;border:.5px solid #2a2d3a">{{ $v->value }}</span>
                    @empty
                    <span style="color:#4a5568">—</span>
                    @endforelse
                </div>
            </td>
            <td style="padding:9px 10px;vertical-align:top">
                <div style="display:flex;gap:3px">
                    <a href="{{ route('variations.edit',$t) }}" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#94a3b8;text-decoration:none"><i class="ti ti-edit" style="font-size:12px"></i></a>
                    <form method="POST" action="{{ route('variations.destroy',$t) }}" onsubmit="return confirm('Delete variation and all its values?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="width:26px;height:26px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#f87171;cursor:pointer"><i class="ti ti-trash" style="font-size:12px"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="3" style="padding:24px;text-align:center;color:#4a5568">No variations yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>
@endsection
