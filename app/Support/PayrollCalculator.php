<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\Staff;

/**
 * Monthly salary figures for one staff member.
 *
 * Two things here were previously wrong and cost employees money:
 *
 *  1. ETF was subtracted from net pay. In Sri Lanka ETF (3%) and the 12% employer
 *     share of EPF are BOTH employer contributions — they are a cost to the shop,
 *     never a deduction from the employee. Only the 8% employee EPF comes out of
 *     the pay packet.
 *  2. `allowances` and `deductions` were stored, displayed and editable, but left
 *     out of the net entirely.
 *
 * Absence model: staff are monthly-paid, so they start from the full contractual
 * salary and lose a day's pay only for an explicit `absent` (or half of one for a
 * `half_day`). Approved leave and holidays are paid. Critically, a day with NO
 * attendance row is treated as worked — attendance here is recorded patchily, and
 * the alternative ("earn only for days marked present") would silently pay someone
 * a fraction of their salary because a manager forgot to fill the sheet.
 */
class PayrollCalculator
{
    /**
     * Full set of payroll column values for a staff member's month.
     *
     * @param  array{allowances?: float, deductions?: float}  $manual
     */
    public static function for(Staff $staff, int $month, int $year, array $manual = []): array
    {
        $cfg = config('hrm.payroll');

        $contract = (float) $staff->basic_salary;
        $daily    = $cfg['working_days_per_month'] > 0 ? $contract / $cfg['working_days_per_month'] : 0.0;
        $hourly   = $cfg['hours_per_day'] > 0 ? $daily / $cfg['hours_per_day'] : 0.0;

        $attendance = Attendance::where('staff_id', $staff->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $presentDays = $attendance->where('status', 'present')->count();
        $halfDays    = $attendance->where('status', 'half_day')->count();
        $leaveDays   = $attendance->where('status', 'leave')->count();
        $absentDays  = $attendance->where('status', 'absent')->count();
        $otHours     = (float) $attendance->sum('overtime_hours');

        // Holidays are informational here (they're already paid — nothing is
        // deducted for them), but they're worth surfacing on the payslip.
        $holidays = $cfg['pay_holidays']
            ? Holiday::whereMonth('date', $month)->whereYear('date', $year)->count()
            : 0;

        // Unpaid time. Approved leave is paid, so it never appears here.
        $unpaidDays = $absentDays + ($halfDays * 0.5);
        if (! $cfg['pay_approved_leave']) {
            $unpaidDays += $leaveDays;
        }

        $basicEarned = max(0.0, $contract - ($unpaidDays * $daily));
        $otPay       = $otHours * $hourly * (float) $cfg['overtime_multiplier'];

        $allowances = (float) ($manual['allowances'] ?? 0);
        $deductions = (float) ($manual['deductions'] ?? 0);

        $gross = $basicEarned + $otPay + $allowances;

        // Statutory contributions are calculated on earned basic.
        $epfEmployee = round($basicEarned * (float) $cfg['epf_employee_rate'], 2);
        $epfEmployer = round($basicEarned * (float) $cfg['epf_employer_rate'], 2);
        $etf         = round($basicEarned * (float) $cfg['etf_rate'], 2);

        // ETF and employer EPF are deliberately absent from this line.
        $net = $gross - $epfEmployee - $deductions;

        return [
            'contract_salary' => round($contract, 2),
            'worked_days'     => round($presentDays + ($halfDays * 0.5) + $leaveDays + $holidays, 1),
            'ot_hours'        => round($otHours, 2),
            'basic_salary'    => round($basicEarned, 2),   // earned, after unpaid absence
            'overtime_pay'    => round($otPay, 2),
            'allowances'      => round($allowances, 2),
            'gross_salary'    => round($gross, 2),
            'deductions'      => round($deductions, 2),
            'epf_employee'    => $epfEmployee,
            'epf_employer'    => $epfEmployer,
            'etf'             => $etf,
            'net_salary'      => round($net, 2),
        ];
    }

    /**
     * Recompute the derived totals on an existing row after allowances or
     * deductions were edited by hand, leaving the attendance-derived parts alone.
     */
    public static function applyTotals(Payroll $payroll): Payroll
    {
        $gross = (float) $payroll->basic_salary
               + (float) $payroll->overtime_pay
               + (float) $payroll->allowances;

        $payroll->gross_salary = round($gross, 2);
        $payroll->net_salary   = round($gross - (float) $payroll->epf_employee - (float) $payroll->deductions, 2);

        return $payroll;
    }

    /** Rupees in words, for the payslip. */
    public static function amountInWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $cents  = (int) round(($amount - $rupees) * 100);

        $words = ucfirst(static::numberToWords($rupees)) . ' rupees';

        if ($cents > 0) {
            $words .= ' and ' . static::numberToWords($cents) . ' cents';
        }

        return $words . ' only';
    }

    private static function numberToWords(int $n): string
    {
        if ($n === 0) {
            return 'zero';
        }

        $units = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
                  'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen',
                  'eighteen', 'nineteen'];
        $tens  = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        // Lakh/crore aren't used here — plain international grouping reads fine on a payslip.
        foreach ([1_000_000_000 => 'billion', 1_000_000 => 'million', 1_000 => 'thousand', 100 => 'hundred'] as $value => $name) {
            if ($n >= $value) {
                $rest = $n % $value;

                return trim(static::numberToWords(intdiv($n, $value)) . ' ' . $name . ($rest ? ' ' . static::numberToWords($rest) : ''));
            }
        }

        if ($n < 20) {
            return $units[$n];
        }

        return trim($tens[intdiv($n, 10)] . ($n % 10 ? '-' . $units[$n % 10] : ''));
    }
}
