{{-- hrm/payroll/payslip.blade.php — management view of one payslip --}}
@extends('layouts.app')
@section('title','Payslip')
@section('page-title','Payslip — '.$staff?->name)
@section('content')
<div style="padding:14px 16px;max-width:900px">

<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('hrm.payroll.index', ['month' => $payroll->month, 'year' => $payroll->year]) }}"
       style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
       <i class="ti ti-arrow-left" style="font-size:14px"></i> Back to payroll</a>
    <a href="{{ route('hrm.payroll.payslip', ['payroll' => $payroll, 'format' => 'pdf']) }}"
       style="height:34px;padding:0 14px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:6px;color:var(--primary-text);font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none;margin-left:auto">
       <i class="ti ti-file-type-pdf" style="font-size:14px"></i> Download PDF</a>
    @can('hrm.payroll.manage')
    @if($payroll->status !== 'paid')
    <form method="POST" action="{{ route('hrm.payroll.paid', $payroll) }}">
        @csrf @method('PATCH')
        <button type="submit" style="height:34px;padding:0 14px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:6px;color:var(--success);font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i class="ti ti-check" style="font-size:14px"></i> Mark as paid</button>
    </form>
    @endif
    @endcan
</div>

@include('hrm.payroll._payslip_body')

</div>
@endsection
