<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body   { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 28px; }
    .head  { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 14px; }
    .shop  { font-size: 17px; font-weight: bold; }
    .sub   { color: #666; font-size: 10px; margin-top: 2px; }
    .title { float: right; text-align: right; }
    .title .t { font-size: 14px; font-weight: bold; }
    .clear { clear: both; }
    .meta  { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .meta td { padding: 3px 0; font-size: 11px; }
    .meta .k { color: #666; width: 110px; }
    table.fig { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.fig th { text-align: left; background: #f0f0f0; border-bottom: 1.5px solid #999; padding: 5px 7px; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
    table.fig td { border-bottom: .5px solid #ddd; padding: 4px 7px; }
    .num   { text-align: right; }
    .tot   { font-weight: bold; background: #f7f7f7; }
    .net   { background: #eaeaea; font-weight: bold; font-size: 12px; }
    .words { font-style: italic; color: #444; margin-bottom: 14px; }
    .note  { color: #666; font-size: 9.5px; border-top: .5px solid #ddd; padding-top: 8px; }
    .cols  { width: 100%; }
    .cols td { vertical-align: top; width: 50%; }
</style>
</head>
<body>

<div class="head">
    <div class="title">
        <div class="t">PAYSLIP</div>
        <div class="sub">{{ $payroll->periodLabel() }}</div>
    </div>
    <div class="shop">{{ config('app.name', 'FreshMart') }}</div>
    <div class="sub">{{ $staff?->branch?->name ?? '' }}</div>
    <div class="clear"></div>
</div>

<table class="meta">
    <tr><td class="k">Employee</td><td><strong>{{ $staff?->name }}</strong></td>
        <td class="k">Pay period</td><td>{{ $payroll->periodLabel() }}</td></tr>
    <tr><td class="k">Job title</td><td>{{ $staff?->role ?? '—' }}</td>
        <td class="k">Days paid</td><td>{{ rtrim(rtrim(number_format((float) $payroll->worked_days, 1), '0'), '.') }}</td></tr>
    <tr><td class="k">Joined</td><td>{{ $staff?->join_date?->format('d M Y') ?? '—' }}</td>
        <td class="k">Status</td><td>{{ ucfirst($payroll->status) }}{{ $payroll->paid_at ? ' on '.$payroll->paid_at->format('d M Y') : '' }}</td></tr>
</table>

<table class="cols">
<tr>
<td style="padding-right:8px">
    <table class="fig">
        <thead><tr><th>Earnings</th><th class="num">Amount (Rs.)</th></tr></thead>
        <tbody>
            <tr><td>Basic salary<br><span style="color:#888;font-size:9px">Contract {{ number_format((float) $payroll->contract_salary, 2) }}</span></td>
                <td class="num">{{ number_format((float) $payroll->basic_salary, 2) }}</td></tr>
            <tr><td>Overtime ({{ rtrim(rtrim(number_format((float) $payroll->ot_hours, 2), '0'), '.') }} hrs)</td>
                <td class="num">{{ number_format((float) $payroll->overtime_pay, 2) }}</td></tr>
            <tr><td>Allowances</td><td class="num">{{ number_format((float) $payroll->allowances, 2) }}</td></tr>
            <tr class="tot"><td>Gross</td><td class="num">{{ number_format((float) $payroll->gross_salary, 2) }}</td></tr>
        </tbody>
    </table>
</td>
<td style="padding-left:8px">
    <table class="fig">
        <thead><tr><th>Deductions</th><th class="num">Amount (Rs.)</th></tr></thead>
        <tbody>
            <tr><td>EPF employee (8%)</td><td class="num">{{ number_format((float) $payroll->epf_employee, 2) }}</td></tr>
            <tr><td>Other deductions</td><td class="num">{{ number_format((float) $payroll->deductions, 2) }}</td></tr>
            <tr><td>&nbsp;</td><td class="num">&nbsp;</td></tr>
            <tr class="tot"><td>Total deductions</td>
                <td class="num">{{ number_format((float) $payroll->epf_employee + (float) $payroll->deductions, 2) }}</td></tr>
        </tbody>
    </table>
</td>
</tr>
</table>

<table class="fig">
    <tr class="net"><td>NET PAY</td><td class="num">Rs. {{ number_format((float) $payroll->net_salary, 2) }}</td></tr>
</table>

<div class="words">{{ $inWords }}</div>

<table class="fig">
    <thead><tr><th>Employer contributions (not deducted from pay)</th><th class="num">Amount (Rs.)</th></tr></thead>
    <tbody>
        <tr><td>EPF employer (12%)</td><td class="num">{{ number_format((float) $payroll->epf_employer, 2) }}</td></tr>
        <tr><td>ETF (3%)</td><td class="num">{{ number_format((float) $payroll->etf, 2) }}</td></tr>
        <tr class="tot"><td>Total cost to employer</td><td class="num">{{ number_format($payroll->employerCost(), 2) }}</td></tr>
    </tbody>
</table>

<div class="note">
    EPF employer and ETF are the employer's statutory contributions — they are paid on top of
    the salary and are not deducted from the employee.<br>
    Computer-generated payslip &middot; {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
