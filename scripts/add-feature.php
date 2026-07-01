<?php

/*
|--------------------------------------------------------------------------
| add-feature.php — append a feature/improvement to Features.md
|--------------------------------------------------------------------------
|
| Inserts a dated, one-line entry at the top of the "Recently Added &
| Requested" list in Features.md, so the document stays current without
| editing it by hand.
|
| Usage:
|   php scripts/add-feature.php "Short title" "Plain-English description"
|
| Examples:
|   php scripts/add-feature.php "Customer SMS receipts" "Text the receipt to the customer after a sale."
|   php scripts/add-feature.php "Dark mode toggle"
|
*/

$file = __DIR__ . '/../Features.md';

$title = $argv[1] ?? null;
$desc  = $argv[2] ?? '';

if ($title === null || trim($title) === '') {
    fwrite(STDERR, "Usage: php scripts/add-feature.php \"Short title\" \"Description (optional)\"\n");
    exit(1);
}

if (! is_file($file)) {
    fwrite(STDERR, "Features.md not found at: {$file}\n");
    exit(1);
}

$marker = '<!-- NEW-FEATURES -->';
$date   = date('Y-m-d');
$title  = trim($title);
$desc   = trim($desc);

$entry = "- **{$date} — {$title}**" . ($desc !== '' ? " — {$desc}" : '');

$content = file_get_contents($file);

if (strpos($content, $marker) === false) {
    fwrite(STDERR, "Marker {$marker} not found in Features.md — cannot insert.\n");
    exit(1);
}

// Insert the new entry on the line right after the marker (newest first).
$content = str_replace($marker, $marker . "\n" . $entry, $content);

file_put_contents($file, $content);

echo "Added to Features.md:\n  {$entry}\n";
