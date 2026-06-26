<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'bill_no',
        'supplier_id',
        'branch_id',
        'user_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'balance_due',
        'payment_status',
        'purchase_date',
        'due_date',
        'notes',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
