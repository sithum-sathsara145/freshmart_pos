<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = ['user_id', 'branch_id', 'name', 'phone', 'email', 'address', 'role', 'basic_salary', 'join_date', 'status'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
    public function appreciations(): HasMany
    {
        return $this->hasMany(Appreciation::class);
    }
}
