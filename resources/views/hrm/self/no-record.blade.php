{{-- hrm/self/no-record.blade.php --}}
@extends('layouts.app')
@section('title','My HR')
@section('page-title','My HR')
@section('content')
<div style="padding:40px 16px;display:flex;justify-content:center">
    <div style="max-width:400px;text-align:center">
        <div style="width:52px;height:52px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:#64748b">
            <i class="ti ti-id-badge-2" style="font-size:24px"></i>
        </div>
        <div style="font-size:14px;color:#e2e8f0;margin-bottom:6px">No staff record yet</div>
        <div style="font-size:12px;color:#64748b;line-height:1.6">
            Your login isn't linked to a staff record, so there's no attendance, leave
            or payslip information to show. An administrator can link it from
            <strong style="color:#94a3b8">Staff&nbsp;Members → the staff member → Edit</strong>.
        </div>
        <a href="{{ route('dashboard') }}" style="display:inline-block;margin-top:16px;height:34px;line-height:34px;padding:0 16px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;text-decoration:none">Back to dashboard</a>
    </div>
</div>
@endsection
