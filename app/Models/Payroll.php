<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'payroll';

    protected $fillable = [
        'staff_id', 'month', 'year',
        'contract_salary', 'worked_days', 'ot_hours',
        'basic_salary', 'overtime_pay', 'allowances', 'gross_salary', 'deductions',
        'epf_employee', 'epf_employer', 'etf', 'net_salary', 'status', 'paid_at',
    ];

    protected $casts = [
        'month'           => 'integer',
        'year'            => 'integer',
        'contract_salary' => 'decimal:2',
        'worked_days'     => 'decimal:1',
        'ot_hours'        => 'decimal:2',
        'basic_salary'    => 'decimal:2',
        'overtime_pay'    => 'decimal:2',
        'allowances'      => 'decimal:2',
        'gross_salary'    => 'decimal:2',
        'deductions'      => 'decimal:2',
        'epf_employee'    => 'decimal:2',
        'epf_employer'    => 'decimal:2',
        'etf'             => 'decimal:2',
        'net_salary'      => 'decimal:2',
        'paid_at'         => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Total cost to the employer: what the employee gets plus statutory contributions. */
    public function employerCost(): float
    {
        return (float) $this->gross_salary + (float) $this->epf_employer + (float) $this->etf;
    }

    public function periodLabel(): string
    {
        return \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
    }
}