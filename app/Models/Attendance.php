<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = ['staff_id', 'date', 'time_in', 'time_out', 'worked_hours', 'overtime_hours', 'status'];

    protected $casts = [
        'date'           => 'date',
        'worked_hours'   => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}