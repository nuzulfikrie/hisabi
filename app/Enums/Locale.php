<?php

declare(strict_types=1);

namespace App\Enums;

enum Locale: string
{
    case ENGLISH = 'en';
    case MALAY = 'ms';

    /**
     * Get the default locale.
     */
    public static function default(): self
    {
        return self::MALAY;
    }

    /**
     * Get all available locales.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $locale): string => $locale->value, self::cases());
    }

    /**
     * Get the display name for the locale.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::MALAY => 'Bahasa Malaysia',
        };
    }

    /**
     * Check if the locale is RTL (Right-to-Left).
     */
    public function isRtl(): bool
    {
        return match ($this) {
            self::ENGLISH, self::MALAY => false,
        };
    }
}
