<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'city', 'is_main', 'status'];

    public function counters(): HasMany
    {
        return $this->hasMany(Counter::class);
    }
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}