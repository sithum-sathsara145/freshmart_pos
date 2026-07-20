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

    /**
     * Store the keys a settings screen owns, from config/settings.php.
     *
     * Only listed keys are written, so a stray or crafted field can't become a
     * setting. Toggles are stored from whether they arrived at all, because a
     * browser omits an unticked checkbox — reading them like ordinary fields
     * would mean a toggle could be switched on but never back off.
     */
    public static function saveGroup(string $group, \Illuminate\Http\Request $request): void
    {
        foreach (config("settings.$group.fields", []) as $key) {
            if ($request->has($key)) {
                static::put($key, (string) $request->input($key));
            }
        }

        foreach (config("settings.$group.toggles", []) as $key) {
            static::put($key, $request->boolean($key) ? '1' : '0');
        }
    }
}
