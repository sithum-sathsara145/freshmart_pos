{{-- hrm/self/no-record.blade.php --}}
@extends('layouts.app')
@section('title','My HR')
@section('page-title','My HR')
@section('content')
<div style="padding:40px 16px;display:flex;justify-content:center">
    <div style="max-width:400px;text-align:center">
        <div style="width:52px;height:52px;background:var(--surface-2);border:.5px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:var(--text-3)">
            <i class="ti ti-id-badge-2" style="font-size:24px"></i>
        </div>
        <div style="font-size:14px;color:var(--text);margin-bottom:6px">No staff record yet</div>
        <div style="font-size:12px;color:var(--text-3);line-height:1.6">
            Your login isn't linked to a staff record, so there's no attendance, leave
            or payslip information to show. An administrator can link it from
            <strong style="color:var(--text-2)">Staff&nbsp;Members → the staff member → Edit</strong>.
        </div>
        <a href="{{ route('dashboard') }}" style="display:inline-block;margin-top:16px;height:34px;line-height:34px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;text-decoration:none">Back to dashboard</a>
    </div>
</div>
@endsection
