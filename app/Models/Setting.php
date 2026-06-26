<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key_name', 'value'];

    public static function get(string $key, $default = null)
    {
        return static::where('key_name', $key)->value('value') ?? $default;
    }
}