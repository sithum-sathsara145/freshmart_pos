<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key_name', 'value'];

    /** Read a plain (non-secret) setting. */
    public static function get(string $key, $default = null)
    {
        $value = static::where('key_name', $key)->value('value');

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Write a plain (non-secret) setting. */
    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key_name' => $key], ['value' => $value]);
    }

    /** Read and decrypt a secret setting. Returns $default if missing/undecryptable. */
    public static function getSecret(string $key, $default = null)
    {
        $value = static::where('key_name', $key)->value('value');
        if ($value === null || $value === '') {
            return $default;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // e.g. APP_KEY changed since this was stored — fall back to default.
            return $default;
        }
    }

    /** Encrypt and write a secret setting; a null/empty value deletes it. */
    public static function putSecret(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            static::where('key_name', $key)->delete();
            return;
        }
        static::updateOrCreate(['key_name' => $key], ['value' => Crypt::encryptString($value)]);
    }

    /** True when a setting currently has a stored value. */
    public static function has(string $key): bool
    {
        return static::where('key_name', $key)->whereNotNull('value')->where('value', '!=', '')->exists();
    }
}
