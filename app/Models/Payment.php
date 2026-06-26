<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['reference_no', 'type', 'account_id', 'to_account_id', 'party_type', 'party_id', 'sale_id', 'purchase_id', 'amount', 'method', 'notes', 'created_by'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
