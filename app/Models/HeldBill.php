<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A parked ("held") POS sale — the full cart state saved as JSON so the cashier
 * can start a fresh sale and resume this one later.
 */
class HeldBill extends Model
{
    protected $fillable = ['branch_id', 'user_id', 'label', 'item_count', 'total', 'payload'];

    protected $casts = [
        'payload'    => 'array',
        'total'      => 'decimal:2',
        'item_count' => 'integer',
    ];
}
