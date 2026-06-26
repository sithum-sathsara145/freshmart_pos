<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class VariationType extends Model
{
    protected $fillable = ['name'];

    public function values(): HasMany
    {
        return $this->hasMany(VariationValue::class);
    }
}