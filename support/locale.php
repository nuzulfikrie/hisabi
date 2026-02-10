<?php

declare(strict_types=1);

use App\Enums\Locale;

if (! function_exists('current_locale')) {
    /**
     * Get the current locale.
     */
    function current_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('set_locale')) {
    /**
     * Set the application locale.
     */
    function set_locale(string $locale): void
    {
        if (in_array($locale, Locale::values(), true)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }
    }
}

if (! function_exists('is_rtl')) {
    /**
     * Check if current locale is RTL.
     */
    function is_rtl(): bool
    {
        return Locale::tryFrom(current_locale())?->isRtl() ?? false;
    }
}
