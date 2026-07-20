{{-- hrm/self/payslip.blade.php — the employee's own copy --}}
@extends('layouts.app')
@section('title','Payslip — '.$payroll->periodLabel())
@section('page-title','My Payslip — '.$payroll->periodLabel())
@section('content')
<div style="padding:14px 16px;max-width:900px">

@include('hrm.self._tabs', ['active' => 'payslips'])

<div style="display:flex;gap:8px;margin-bottom:14px">
    <a href="{{ route('my.payslips') }}"
       style="height:34px;padding:0 12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none">
       <i class="ti ti-arrow-left" style="font-size:14px"></i> All payslips</a>
    <a href="{{ route('my.payslip', ['payroll' => $payroll->id, 'format' => 'pdf']) }}"
       style="height:34px;padding:0 14px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:6px;color:var(--primary-text);font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none;margin-left:auto">
       <i class="ti ti-file-type-pdf" style="font-size:14px"></i> Download PDF</a>
</div>

{{-- Same partial the management view renders, so an employee and their manager
     can never be shown different figures for the same month. --}}
@include('hrm.payroll._payslip_body')

</div>
@endsection
