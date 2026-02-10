<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case MYR = 'MYR';
    case AED = 'AED';
    case USD = 'USD';
    case SGD = 'SGD';

    /**
     * Get the default currency.
     */
    public static function default(): self
    {
        return self::MYR;
    }

    /**
     * Get all available currencies.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $currency): string => $currency->value, self::cases());
    }

    /**
     * Get the display name for the currency.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::MYR => 'Malaysian Ringgit',
            self::AED => 'UAE Dirham',
            self::USD => 'US Dollar',
            self::SGD => 'Singapore Dollar',
        };
    }

    /**
     * Get the currency symbol.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::MYR => 'RM',
            self::AED => 'AED',
            self::USD => '$',
            self::SGD => 'S$',
        };
    }

    /**
     * Get the number of decimal places for the currency.
     */
    public function decimalPlaces(): int
    {
        return 2;
    }
}
