<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Parses weighing-scale "embedded" EAN-13 barcodes (GS1 prefix-2 / in-store range).
 *
 * Default layout — the common CAS / Essae-Teraoka setup in Sri Lankan shops:
 *
 *     2  PPPPP  VVVVV  C        (13 digits)
 *     │  │      │      └ EAN-13 check digit (validated by the scanner, ignored here)
 *     │  │      └──────── embedded value: price (default) or weight
 *     │  └─────────────── PLU / item code  →  products.scale_plu
 *     └────────────────── flag "2"  (never used by real retail barcodes)
 *
 * Every segment is configurable from Settings → Scale barcodes, so other scale
 * formats are supported without code changes. Returns null for anything that
 * isn't a scale barcode, so ordinary product barcodes fall through untouched.
 */
class ScaleBarcode
{
    /** @return array{plu:string, value:float, embed:string}|null */
    public static function parse(string $code): ?array
    {
        $code = trim($code);

        if (! self::enabled() || $code === '' || ! ctype_digit($code)) {
            return null;
        }

        $prefix  = (string) self::cfg('scale_prefix', '2');
        $length  = (int)    self::cfg('scale_total_length', 13);
        $pluLen  = (int)    self::cfg('scale_plu_length', 5);
        $valLen  = (int)    self::cfg('scale_value_length', 5);
        $embed   = self::cfg('scale_embed', 'price') === 'weight' ? 'weight' : 'price';
        $divisor = (float)  self::cfg('scale_value_divisor', 100);

        if ($prefix === '' || strlen($code) !== $length || ! str_starts_with($code, $prefix)) {
            return null;
        }

        $start = strlen($prefix);
        // PLU sits right after the prefix; the value occupies the last digits before the
        // final EAN-13 check digit. Any digits between them (e.g. a price-check digit) are
        // ignored. This matches both "2 IIIII C PPPPP C" and "2 IIIIII PPPPP C" layouts.
        $valStart = $length - 1 - $valLen;
        if ($pluLen < 1 || $valLen < 1 || $valStart < $start + $pluLen) {
            return null; // misconfigured / overlapping — don't misread a real barcode
        }

        $plu = substr($code, $start, $pluLen);
        $val = substr($code, $valStart, $valLen);
        if (! ctype_digit($plu) || ! ctype_digit($val)) {
            return null;
        }

        return [
            'plu'   => (string) (int) $plu,                               // drop leading zeros for matching
            'value' => $divisor > 0 ? ((int) $val) / $divisor : (int) $val,
            'embed' => $embed,
        ];
    }

    public static function enabled(): bool
    {
        return (string) self::cfg('scale_enabled', '0') === '1';
    }

    /** Read a setting with a safe fallback (DB may be unavailable during install). */
    private static function cfg(string $key, $default)
    {
        try {
            $value = Setting::get($key);
        } catch (\Throwable $e) {
            return $default;
        }
        return ($value === null || $value === '') ? $default : $value;
    }
}
