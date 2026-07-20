<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;

/**
 * CSV / XLSX plumbing shared by every import and export in the app.
 *
 * Imports are written by shopkeepers in Excel, so the readers are deliberately
 * forgiving: headers are matched case-insensitively, blank lines are dropped,
 * and numbers arrive with thousands separators and currency symbols attached.
 */
class Spreadsheet
{
    /** File extensions an upload is allowed to use. */
    public const ACCEPTED = ['csv', 'txt', 'xlsx'];

    /** Normalise an uploaded file's extension to a reader type, or null if unsupported. */
    public static function typeFor(string $extension): ?string
    {
        $extension = strtolower($extension);

        if (! in_array($extension, self::ACCEPTED, true)) {
            return null;
        }

        return $extension === 'xlsx' ? 'xlsx' : 'csv';
    }

    /**
     * Read the first sheet into a list of header-keyed rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function read(string $path, string $type): array
    {
        $reader = $type === 'xlsx'
            ? new \OpenSpout\Reader\XLSX\Reader()
            : new \OpenSpout\Reader\CSV\Reader();

        $reader->open($path);
        $rows    = [];
        $headers = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row->toArray());

                if ($headers === null) {
                    $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $cells);
                    continue;
                }
                if (count(array_filter($cells, fn ($v) => $v !== '' && $v !== null)) === 0) {
                    continue;   // blank line
                }

                $assoc = [];
                foreach ($headers as $i => $h) {
                    if ($h !== '') {
                        $assoc[$h] = $cells[$i] ?? null;
                    }
                }
                $rows[] = $assoc;
            }
            break;   // first sheet only
        }
        $reader->close();

        return $rows;
    }

    /** Write headers + rows to a CSV/XLSX file and return it as a download. */
    public static function download(array $headers, iterable $rows, string $format, string $basename)
    {
        $writer = $format === 'xlsx'
            ? new \OpenSpout\Writer\XLSX\Writer()
            : new \OpenSpout\Writer\CSV\Writer();

        $tmp = tempnam(sys_get_temp_dir(), 'exp');
        $writer->openToFile($tmp);
        $writer->addRow(Row::fromValues($headers));
        foreach ($rows as $row) {
            $clean = array_map(fn ($v) => $v === null ? '' : $v, $row);
            $writer->addRow(Row::fromValues($clean));
        }
        $writer->close();

        $mime = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv';

        return response()->download($tmp, $basename . '.' . $format, ['Content-Type' => $mime])
                         ->deleteFileAfterSend(true);
    }

    /** "Rs. 1,250.50" -> 1250.50 */
    public static function num($v): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $v);
    }

    /** Accepts the spellings a person actually types for "yes". */
    public static function bool($v): bool
    {
        return in_array(strtolower(trim((string) $v)), ['1', 'yes', 'y', 'true', 'active'], true);
    }

    /** True when the row actually carries a value for $key — blanks must never wipe existing data. */
    public static function has(array $row, string $key): bool
    {
        return array_key_exists($key, $row) && trim((string) ($row[$key] ?? '')) !== '';
    }
}
