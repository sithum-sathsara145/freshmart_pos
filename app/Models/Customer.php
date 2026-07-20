<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    protected $fillable = ['name','phone','email','address','nic','credit_approved','credit_limit','loyalty_points','loyalty_level','total_purchases'];

    protected $casts = [
        'credit_approved' => 'boolean',
        'credit_limit'    => 'decimal:2',
    ];

    public function sales(): HasMany { return $this->hasMany(Sale::class); }

    /** Total unpaid balance across this customer's credit/partial sales. */
    public function outstandingBalance(): float
    {
        return (float) $this->sales()
            ->whereColumn('paid_amount', '<', 'total')
            ->sum(DB::raw('total - paid_amount'));
    }

    public function addLoyaltyPoints(float $amount): void
    {
        $points = (int) ($amount / 20);
        $this->increment('loyalty_points', $points);
        $this->updateLoyaltyLevel();
    }

    private function updateLoyaltyLevel(): void
    {
        $level = match(true) {
            $this->loyalty_points >= 5000  => 'platinum',
            $this->loyalty_points >= 3000  => 'gold',
            $this->loyalty_points >= 1000  => 'silver',
            default                        => 'bronze',
        };
        $this->update(['loyalty_level' => $level]);
    }
}
