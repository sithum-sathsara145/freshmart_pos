<?php
/**
 * Theme token generator.
 *
 * The app UI is styled with inline `style="..."` attributes rather than utility
 * classes, so theming works by pointing every colour at a CSS variable and
 * swapping the variable values on `html.dark`. This table is the source of
 * truth for those values; running the script rewrites the generated block in
 * resources/views/partials/theme.blade.php between the tokens:start/end markers.
 *
 * The dark column is the palette the app originally hardcoded, so dark mode
 * still looks exactly the way it always did. The light column is the new one.
 *
 * Usage:  php scripts/theme-tokens.php
 */

// section => [ token => [light, dark] ]
$tokens = [
    'Surfaces' => [
        'bg'             => ['#f4f6f9', '#0f1117'],  // page background, input fills
        'surface'        => ['#ffffff', '#161821'],  // cards, sidebar, top bar
        'surface-2'      => ['#eef1f6', '#1e2130'],  // hovers, dropdowns, chips
        'surface-3'      => ['#f7f8fb', '#1a1d2a'],
        'surface-4'      => ['#e4e8f0', '#252840'],
        'surface-active' => ['#e7ebf3', '#191c2b'],
        'surface-inv'    => ['#e9edf4', '#111827'],
        'sunken'         => ['#eaedf3', '#13151d'],
        'sunken-2'       => ['#e5e9f0', '#0b0c11'],
        'border'         => ['#dde2ea', '#2a2d3a'],
    ],
    'Text' => [
        'text'    => ['#1e293b', '#e2e8f0'],  // primary copy
        'text-hi' => ['#334155', '#cbd5e1'],
        'text-2'  => ['#51607a', '#94a3b8'],  // secondary copy
        'text-3'  => ['#6b7688', '#64748b'],  // muted labels
        // The faint labels (sidebar sections, empty states) sat at ~2.4:1 in the
        // original dark palette. Dark is left as it was, but light is new, so
        // these two are set just dark enough to clear 3:1 on both --surface and
        // --bg while still reading as muted.
        'text-4'  => ['#828d9f', '#4a5568'],  // faintest
        'text-5'  => ['#7d8899', '#475569'],
    ],
    'Primary (indigo)' => [
        'primary'            => ['#4f46e5', '#818cf8'],
        'primary-text'       => ['#4338ca', '#a5b4fc'],
        'primary-text-2'     => ['#6d28d9', '#c4b5fd'],
        'primary-soft'       => ['#e0e7ff', '#312e81'],
        'primary-soft-hover' => ['#c7d2fe', '#3c3a96'],
        'primary-border'     => ['#c7d2fe', '#534ab7'],
        'primary-deep'       => ['#ddd6fe', '#4c1d95'],
        // The *-solid tokens back buttons that carry white text, so they have to
        // stay dark enough for white to read on them in the light theme too.
        'primary-solid'      => ['#4f46e5', '#534ab7'],
    ],
    'Success' => [
        'success'        => ['#15803d', '#4ade80'],
        'success-soft'   => ['#dcfce7', '#14532d'],
        'success-soft-2' => ['#f0fdf4', '#0f2a1b'],
        'success-border' => ['#86efac', '#166534'],
        'success-solid'  => ['#059669', '#1d9e75'],
    ],
    'Danger' => [
        'danger'          => ['#dc2626', '#f87171'],
        'danger-2'        => ['#b91c1c', '#ef4444'],
        'danger-text'     => ['#b91c1c', '#fca5a5'],
        'danger-soft'     => ['#fee2e2', '#7f1d1d'],
        'danger-soft-2'   => ['#fef2f2', '#3f1d1d'],
        'danger-soft-3'   => ['#fef2f2', '#3a1414'],
        'danger-border'   => ['#fecaca', '#991b1b'],
        'danger-border-2' => ['#fca5a5', '#b91c1c'],
        'danger-solid'    => ['#dc2626', '#7f1d1d'],
    ],
    'Warning' => [
        'warning'          => ['#c2410c', '#fb923c'],
        'warning-2'        => ['#a16207', '#fbbf24'],
        'warning-soft'     => ['#ffedd5', '#451a03'],
        'warning-soft-2'   => ['#fef3c7', '#422006'],
        'warning-soft-3'   => ['#fef3c7', '#3a2c0c'],
        'warning-soft-4'   => ['#fef9c3', '#3f2d0a'],
        'warning-border'   => ['#fcd34d', '#854d0e'],
        'warning-border-2' => ['#fcd34d', '#78531a'],
        'warning-solid'    => ['#b45309', '#b45309'],
    ],
    'Info' => [
        'info'       => ['#2563eb', '#60a5fa'],
        'info-soft'  => ['#dbeafe', '#1e3a5f'],
        'info-solid' => ['#0e7490', '#0e7490'],
    ],
    'Overlay & shadow' => [
        'overlay' => ['rgba(15,23,42,.45)', 'rgba(8,9,13,.72)'],  // modal backdrops
        'shadow'  => ['rgba(15,23,42,.14)', 'rgba(0,0,0,.5)'],    // drop shadows
    ],
];

$partial = dirname(__DIR__) . '/resources/views/partials/theme.blade.php';

$css = "    /* tokens:start — generated, do not edit by hand */\n";
foreach ([['light', ':root', 0], ['dark', 'html.dark', 1]] as [$mode, $selector, $index]) {
    $css .= "    $selector {\n";
    $css .= "        color-scheme: $mode;\n";
    foreach ($tokens as $section => $group) {
        $css .= "\n        /* $section */\n";
        foreach ($group as $token => $values) {
            $css .= sprintf("        --%-19s %s;\n", $token . ':', $values[$index]);
        }
    }
    $css .= "    }\n\n";
}
$css .= "    /* tokens:end */";

$content = file_get_contents($partial);
$updated = preg_replace(
    '/    \/\* tokens:start.*?\/\* tokens:end \*\//s',
    // Guard the replacement against $-sequences in the CSS being read as backrefs.
    str_replace('$', '\\$', $css),
    $content,
    1,
    $n
);

if ($n !== 1) {
    fwrite(STDERR, "error: tokens:start/end markers not found in $partial\n");
    exit(1);
}

file_put_contents($partial, $updated);

$count = array_sum(array_map('count', $tokens));
echo "wrote $count tokens (light + dark) to resources/views/partials/theme.blade.php\n";
