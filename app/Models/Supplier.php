<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address', 'city', 'total_purchases', 'balance_due'];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
