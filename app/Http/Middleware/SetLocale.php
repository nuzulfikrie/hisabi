<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * The configuration key for the default locale.
     */
    private const CONFIG_DEFAULT_LOCALE_KEY = 'hisabi.default_locale';

    /**
     * The configuration key for available locales.
     */
    private const CONFIG_AVAILABLE_LOCALES_KEY = 'hisabi.available_locales';

    /**
     * The session key for storing the user's locale preference.
     */
    private const SESSION_LOCALE_KEY = 'locale';

    /**
     * The header key for accepting locale in API requests.
     */
    private const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale->value);

        return $next($request);
    }

    /**
     * Resolve the locale from various sources.
     */
    private function resolveLocale(Request $request): Locale
    {
        // Check session first (if session is available)
        if ($request->hasSession()) {
            $sessionLocale = $request->session()->get(self::SESSION_LOCALE_KEY);
            if ($sessionLocale !== null) {
                $locale = Locale::tryFrom($sessionLocale);
                if ($locale !== null && $this->isValidLocale($locale)) {
                    return $locale;
                }
            }
        }

        // Check Accept-Language header
        $acceptLanguage = $request->header(self::HEADER_ACCEPT_LANGUAGE);
        if ($acceptLanguage !== null && $acceptLanguage !== '') {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale !== null) {
                return $locale;
            }
        }

        // Return default locale from config
        $defaultLocaleValue = config(self::CONFIG_DEFAULT_LOCALE_KEY, Locale::default()->value);
        $defaultLocale = Locale::tryFrom($defaultLocaleValue);

        return $defaultLocale ?? Locale::default();
    }

    /**
     * Check if the locale is valid (in available locales list).
     */
    private function isValidLocale(Locale $locale): bool
    {
        $availableLocales = config(self::CONFIG_AVAILABLE_LOCALES_KEY, Locale::values());

        return in_array($locale->value, $availableLocales, true);
    }

    /**
     * Parse Accept-Language header and return matching locale.
     */
    private function parseAcceptLanguage(string $acceptLanguage): ?Locale
    {
        $availableLocales = config(self::CONFIG_AVAILABLE_LOCALES_KEY, Locale::values());

        // Parse language ranges (e.g., "en-US,en;q=0.9,ms;q=0.8")
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            // Extract language code without quality factor
            $parts = explode(';', $language);
            $langCode = trim($parts[0]);

            // Check for exact match first
            $locale = Locale::tryFrom($langCode);
            if ($locale !== null && in_array($locale->value, $availableLocales, true)) {
                return $locale;
            }

            // Check for base language match (e.g., "en" from "en-US")
            $baseCode = explode('-', $langCode)[0];
            $locale = Locale::tryFrom($baseCode);
            if ($locale !== null && in_array($locale->value, $availableLocales, true)) {
                return $locale;
            }
        }

        return null;
    }
}
