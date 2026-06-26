<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = ['from_branch_id', 'to_branch_id', 'product_id', 'quantity', 'status', 'notes', 'created_by'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }
}
