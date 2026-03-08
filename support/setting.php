<?php

declare(strict_types=1);

if (! function_exists('setting')) {
    /**
     * Get a setting value by key.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Models\Setting::get($key, $default);
    }
}

if (! function_exists('setting_set')) {
    /**
     * Set a setting value by key.
     */
    function setting_set(string $key, mixed $value): void
    {
        \App\Models\Setting::set($key, $value);
    }
}
