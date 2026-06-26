<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
