<?php

/*
 * Small formatting helpers used by the order detail views (sales, artist,
 * admin, boss, data entry). They used to be declared inside each view, which
 * fails with "Cannot redeclare function" when two of those views render in
 * the same PHP process. Loaded once from AppServiceProvider.
 */

if (! function_exists('dd_method_label')) {
    /** Delivery method code -> label. */
    function dd_method_label($v)
    {
        $v = strtolower((string) $v);

        return [
            'courier'               => 'Courier',
            'self_pickup'           => 'Self Pickup',
            'pickup'                => 'Pickup',
            'installation'          => 'Installation',
            'delivery_installation' => 'Delivery & Installation',
        ][$v] ?? ucfirst($v ?: '-');
    }
}

if (! function_exists('dd_datetime')) {
    /** Readable date/time from separate date and time columns. */
    function dd_datetime(?string $d, ?string $t)
    {
        if (! $d && ! $t) {
            return '—';
        }
        try {
            if ($d && $t) {
                return \Carbon\Carbon::parse("$d $t")->format('M d, Y · h:i A');
            }
            if ($d) {
                return \Carbon\Carbon::parse($d)->format('M d, Y');
            }

            return \Carbon\Carbon::parse($t)->format('h:i A');
        } catch (\Throwable $e) {
            return trim(($d ?: '') . ' ' . $t) ?: '—';
        }
    }
}

if (! function_exists('yn')) {
    /** Yes/No from a tinyint or null. */
    function yn($v)
    {
        return ((int) $v) === 1 ? 'Yes' : 'No';
    }
}
