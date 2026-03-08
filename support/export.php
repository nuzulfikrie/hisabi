<?php

declare(strict_types=1);

if (! function_exists('export_filename')) {
    /**
     * Generate an export filename with timestamp.
     */
    function export_filename(string $prefix, string $ext = 'xlsx'): string
    {
        return $prefix.'_'.now()->format('Ymd_His').'.'.$ext;
    }
}
