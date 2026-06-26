<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['staff_id', 'date', 'time_in', 'time_out', 'worked_hours', 'overtime_hours', 'status'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}