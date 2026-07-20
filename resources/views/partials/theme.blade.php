{{-- Theme tokens.

     Every colour in the app UI resolves through one of these variables, so a
     theme is just a different set of values on the root element. Light is the
     default; `html.dark` restores the original dark palette.

     Printed views (receipts, invoices, payslips, barcode sheets) deliberately
     do NOT include this partial — they stay paper-coloured in both themes.

     The token block below is generated: edit the table in scripts/theme-tokens.php
     and re-run it rather than hand-editing the values here. --}}

<script>
    // Runs before the first paint: a dark-mode user must never see the light
    // palette flash in while the rest of the page loads.
    (function () {
        try {
            if (localStorage.getItem('freshmart-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) { /* private mode — fall back to the light default */ }
    })();

    function toggleTheme() {
        var root = document.documentElement;

        // Every token changes at once here. Any property that is mid-transition
        // on a var() value latches onto the outgoing colour and never lands on
        // the new one, so a themed control keeps its old background while its
        // untransitioned neighbours flip. Freezing transitions across the swap
        // and forcing a reflow commits the new values cleanly.
        root.classList.add('theme-switching');

        var dark = root.classList.toggle('dark');
        try {
            localStorage.setItem('freshmart-theme', dark ? 'dark' : 'light');
        } catch (e) { /* choice just won't persist */ }

        void root.offsetWidth;
        root.classList.remove('theme-switching');
    }
</script>

<style>
    /* tokens:start — generated, do not edit by hand */
    :root {
        color-scheme: light;

        /* Surfaces */
        --bg:                 #f4f6f9;
        --surface:            #ffffff;
        --surface-2:          #eef1f6;
        --surface-3:          #f7f8fb;
        --surface-4:          #e4e8f0;
        --surface-active:     #e7ebf3;
        --surface-inv:        #e9edf4;
        --sunken:             #eaedf3;
        --sunken-2:           #e5e9f0;
        --border:             #dde2ea;

        /* Text */
        --text:               #1e293b;
        --text-hi:            #334155;
        --text-2:             #51607a;
        --text-3:             #6b7688;
        --text-4:             #828d9f;
        --text-5:             #7d8899;

        /* Primary (indigo) */
        --primary:            #4f46e5;
        --primary-text:       #4338ca;
        --primary-text-2:     #6d28d9;
        --primary-soft:       #e0e7ff;
        --primary-soft-hover: #c7d2fe;
        --primary-border:     #c7d2fe;
        --primary-deep:       #ddd6fe;
        --primary-solid:      #4f46e5;

        /* Success */
        --success:            #15803d;
        --success-soft:       #dcfce7;
        --success-soft-2:     #f0fdf4;
        --success-border:     #86efac;
        --success-solid:      #059669;

        /* Danger */
        --danger:             #dc2626;
        --danger-2:           #b91c1c;
        --danger-text:        #b91c1c;
        --danger-soft:        #fee2e2;
        --danger-soft-2:      #fef2f2;
        --danger-soft-3:      #fef2f2;
        --danger-border:      #fecaca;
        --danger-border-2:    #fca5a5;
        --danger-solid:       #dc2626;

        /* Warning */
        --warning:            #c2410c;
        --warning-2:          #a16207;
        --warning-soft:       #ffedd5;
        --warning-soft-2:     #fef3c7;
        --warning-soft-3:     #fef3c7;
        --warning-soft-4:     #fef9c3;
        --warning-border:     #fcd34d;
        --warning-border-2:   #fcd34d;
        --warning-solid:      #b45309;

        /* Info */
        --info:               #2563eb;
        --info-soft:          #dbeafe;
        --info-solid:         #0e7490;

        /* Overlay & shadow */
        --overlay:            rgba(15,23,42,.45);
        --shadow:             rgba(15,23,42,.14);
    }

    html.dark {
        color-scheme: dark;

        /* Surfaces */
        --bg:                 #0f1117;
        --surface:            #161821;
        --surface-2:          #1e2130;
        --surface-3:          #1a1d2a;
        --surface-4:          #252840;
        --surface-active:     #191c2b;
        --surface-inv:        #111827;
        --sunken:             #13151d;
        --sunken-2:           #0b0c11;
        --border:             #2a2d3a;

        /* Text */
        --text:               #e2e8f0;
        --text-hi:            #cbd5e1;
        --text-2:             #94a3b8;
        --text-3:             #64748b;
        --text-4:             #4a5568;
        --text-5:             #475569;

        /* Primary (indigo) */
        --primary:            #818cf8;
        --primary-text:       #a5b4fc;
        --primary-text-2:     #c4b5fd;
        --primary-soft:       #312e81;
        --primary-soft-hover: #3c3a96;
        --primary-border:     #534ab7;
        --primary-deep:       #4c1d95;
        --primary-solid:      #534ab7;

        /* Success */
        --success:            #4ade80;
        --success-soft:       #14532d;
        --success-soft-2:     #0f2a1b;
        --success-border:     #166534;
        --success-solid:      #1d9e75;

        /* Danger */
        --danger:             #f87171;
        --danger-2:           #ef4444;
        --danger-text:        #fca5a5;
        --danger-soft:        #7f1d1d;
        --danger-soft-2:      #3f1d1d;
        --danger-soft-3:      #3a1414;
        --danger-border:      #991b1b;
        --danger-border-2:    #b91c1c;
        --danger-solid:       #7f1d1d;

        /* Warning */
        --warning:            #fb923c;
        --warning-2:          #fbbf24;
        --warning-soft:       #451a03;
        --warning-soft-2:     #422006;
        --warning-soft-3:     #3a2c0c;
        --warning-soft-4:     #3f2d0a;
        --warning-border:     #854d0e;
        --warning-border-2:   #78531a;
        --warning-solid:      #b45309;

        /* Info */
        --info:               #60a5fa;
        --info-soft:          #1e3a5f;
        --info-solid:         #0e7490;

        /* Overlay & shadow */
        --overlay:            rgba(8,9,13,.72);
        --shadow:             rgba(0,0,0,.5);
    }

    /* tokens:end */

    /* Held only for the instant the palette is swapped — see toggleTheme(). */
    html.theme-switching,
    html.theme-switching *,
    html.theme-switching *::before,
    html.theme-switching *::after {
        transition: none !important;
    }

    /* Toggle control. The icon shows the theme you would switch *to*. */
    .theme-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        background: var(--surface-2);
        border: .5px solid var(--border);
        border-radius: 6px;
        color: var(--text-2);
        font-size: 14px;
        cursor: pointer;
        transition: color .12s, background .12s;
    }
    .theme-toggle:hover { color: var(--text); background: var(--surface-4); }
    .theme-toggle .ti-sun { display: none; }
    html.dark .theme-toggle .ti-sun { display: inline; }
    html.dark .theme-toggle .ti-moon { display: none; }
</style>
