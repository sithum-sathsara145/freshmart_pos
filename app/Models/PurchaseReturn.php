<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = ['dr_note_no', 'purchase_id', 'supplier_id', 'reason', 'return_amount', 'credit_method', 'status', 'created_by'];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}