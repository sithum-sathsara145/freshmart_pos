<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'invoice_no',
        'customer_id',
        'branch_id',
        'counter_id',
        'user_id',
        'coupon_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'notes',
        'is_online_order',
    ];

    protected $casts = ['total' => 'decimal:2', 'paid_amount' => 'decimal:2'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function balanceDue(): float
    {
        return max(0, $this->total - $this->paid_amount);
    }
    public function scopePaid($q)
    {
        return $q->where('status', 'paid');
    }
    public function scopePartial($q)
    {
        return $q->where('status', 'partial');
    }
}