<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name','phone','email','address','loyalty_points','loyalty_level','total_purchases'];

    public function sales(): HasMany { return $this->hasMany(Sale::class); }

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
