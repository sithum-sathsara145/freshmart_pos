{{-- hrm/payroll/payslip.blade.php — management view of one payslip --}}
@extends('layouts.app')
@section('title','Payslip')
@section('page-title','Payslip — '.$staff?->name)
@section('content')
<div style="padding:14px 16px;max-width:900px">

<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('hrm.payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}"
       style="height:34px;padding:0 12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
       <i class="ti ti-arrow-left" style="font-size:14px"></i> Back to payroll</a>
    <a href="{{ route('hrm.payroll.payslip', ['payroll' => $payroll, 'format' => 'pdf']) }}"
       style="height:34px;padding:0 14px;background:#312e81;border:.5px solid #534AB7;border-radius:6px;color:#a5b4fc;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none;margin-left:auto">
       <i class="ti ti-file-type-pdf" style="font-size:14px"></i> Download PDF</a>
    @can('hrm.payroll.manage')
    @if($payroll->status !== 'paid')
    <form method="POST" action="{{ route('hrm.payroll.paid', $payroll) }}">
        @csrf @method('PATCH')
        <button type="submit" style="height:34px;padding:0 14px;background:#14532d;border:.5px solid #166534;border-radius:6px;color:#4ade80;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-check" style="font-size:14px"></i> Mark as paid</button>
    </form>
    @endif
    @endcan
</div>

@include('hrm.payroll._payslip_body')

</div>
@endsection
