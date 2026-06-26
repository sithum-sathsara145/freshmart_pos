<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'payroll';

    protected $fillable = ['staff_id', 'month', 'year', 'basic_salary', 'overtime_pay', 'allowances', 'deductions', 'epf_employee', 'epf_employer', 'etf', 'net_salary', 'status', 'paid_at'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}