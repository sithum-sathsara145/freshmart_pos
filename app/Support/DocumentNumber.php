<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Hands out invoice / bill / quote / note numbers.
 *
 * These used to be worked out by reading the last row and adding one, which two
 * tills finishing a sale at the same moment would both do — producing the same
 * number and failing one of the sales on the UNIQUE index. Numbers now come from
 * a counter row bumped by a single atomic statement, so concurrent callers are
 * serialised on that row and each gets its own number.
 *
 * Called inside the caller's transaction, so a rolled-back sale gives its number
 * back instead of leaving a hole in the sequence.
 */
class DocumentNumber
{
    /** key => [prefix, zero-padding, table to seed from, its number column] */
    public const DOCUMENTS = [
        'invoice'     => ['INV-', 6, 'sales',            'invoice_no'],
        'purchase'    => ['PO-',  5, 'purchases',        'bill_no'],
        'quotation'   => ['QT-',  4, 'quotations',       'quote_no'],
        'credit_note' => ['CR-',  4, 'sale_returns',     'credit_note_no'],
        'debit_note'  => ['DR-',  4, 'purchase_returns', 'dr_note_no'],
    ];

    /** e.g. next('invoice') => "INV-000124" */
    public static function next(string $key): string
    {
        if (! isset(self::DOCUMENTS[$key])) {
            throw new \InvalidArgumentException("Unknown document type [{$key}].");
        }

        [$prefix, $pad] = self::DOCUMENTS[$key];

        return $prefix . str_pad((string) self::bump($key), $pad, '0', STR_PAD_LEFT);
    }

    /**
     * The highest number already used for a document type, read from the rows
     * themselves. Used to seed the counter so numbering carries on rather than
     * restarting at 1 and colliding with history.
     */
    /**
     * Numbers are read in PHP rather than SQL because this runs on MySQL 5.7,
     * which has no REGEXP_REPLACE. Only runs when a counter is first seeded, so
     * reading the column through is fine.
     *
     * Two shapes are in the data: ones this code generated (INV-002028) and
     * older ones carrying a year (INV-2025-0010). Only the generator's own shape
     * counts towards the maximum — taking the year-style ones literally would
     * read as twenty million and shunt every future number up with it. When a
     * table holds nothing but year-style numbers there is no sequence to
     * continue, so it falls back to the digit strip the old generators used and
     * numbering carries on exactly where it would have.
     */
    public static function highestUsed(string $key): int
    {
        [$prefix, , $table, $column] = self::DOCUMENTS[$key];

        $ownFormat = 0;
        $anyDigits = 0;
        $pattern   = '/^' . preg_quote($prefix, '/') . '(\d+)$/';

        DB::table($table)->select($column)->orderBy('id')
            ->chunk(2000, function ($rows) use ($column, $pattern, &$ownFormat, &$anyDigits) {
                foreach ($rows as $row) {
                    $value = (string) $row->{$column};

                    if (preg_match($pattern, $value, $m)) {
                        $ownFormat = max($ownFormat, (int) $m[1]);
                    }

                    $digits = preg_replace('/\D/', '', $value);
                    if ($digits !== '') {
                        $anyDigits = max($anyDigits, (int) $digits);
                    }
                }
            });

        return $ownFormat ?: $anyDigits;
    }

    /** Atomically take the next number for $key. */
    private static function bump(string $key): int
    {
        // LAST_INSERT_ID(expr) stores expr for this connection only, so the value
        // read back is the one this statement produced even under concurrency.
        $affected = DB::affectingStatement(
            'UPDATE document_sequences SET next_number = LAST_INSERT_ID(next_number + 1) WHERE key_name = ?',
            [$key]
        );

        if ($affected === 0) {
            // No counter row yet — normally seeded by scripts/apply-schema-updates.php,
            // so this only runs on an install that has not been through it.
            self::seed($key);

            DB::affectingStatement(
                'UPDATE document_sequences SET next_number = LAST_INSERT_ID(next_number + 1) WHERE key_name = ?',
                [$key]
            );
        }

        return (int) DB::selectOne('SELECT LAST_INSERT_ID() AS n')->n;
    }

    /** Create the counter row at the highest number already in use. */
    public static function seed(string $key): void
    {
        DB::statement(
            'INSERT INTO document_sequences (key_name, next_number, created_at, updated_at)
             VALUES (?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE next_number = GREATEST(next_number, VALUES(next_number))',
            [$key, self::highestUsed($key)]
        );
    }
}
