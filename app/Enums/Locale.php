<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum Locale: string implements Enum
{
    use InteractsWithEnum;

    case ENGLISH = 'en';
    case MALAY = 'ms';

    /**
     * Get the default locale.
     */
    public static function default(): self
    {
        return self::MALAY;
    }

    public function label(): string
    {
        return $this->displayName();
    }

    public function description(): string
    {
        return match ($this) {
            self::ENGLISH => __('English language - International'),
            self::MALAY => __('Bahasa Malaysia - National language of Malaysia'),
        };
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
