<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveEntitlement extends Model
{
    protected $fillable = ['staff_id', 'year', 'type', 'entitled_days'];

    protected $casts = [
        'year'          => 'integer',
        'entitled_days' => 'decimal:1',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
