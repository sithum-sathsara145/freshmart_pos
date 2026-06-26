<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Appreciation extends Model
{
    protected $fillable = ['staff_id', 'category', 'note', 'given_by'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
    public function givenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'given_by');
    }
}