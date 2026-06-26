<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'min_order_amount', 'max_uses', 'used_count', 'expires_at', 'status'];

    public function isValid(float $orderAmount = 0): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at >= today())
            && ($this->max_uses === null || $this->used_count < $this->max_uses)
            && $orderAmount >= $this->min_order_amount;
    }

    public function calculateDiscount(float $amount): float
    {
        return $this->type === 'percentage'
            ? round($amount * $this->value / 100, 2)
            : min($this->value, $amount);
    }
}
