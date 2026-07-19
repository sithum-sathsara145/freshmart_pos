<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $table    = 'leave_requests';
    protected $fillable = ['staff_id', 'type', 'from_date', 'to_date', 'days', 'reason', 'status', 'approved_by'];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'days'      => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}