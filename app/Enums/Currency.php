<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum Currency: string implements Enum
{
    use InteractsWithEnum;

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

    public function label(): string
    {
        return $this->displayName();
    }

    public function description(): string
    {
        return match ($this) {
            self::MYR => __('Malaysian Ringgit - Official currency of Malaysia'),
            self::AED => __('UAE Dirham - Official currency of United Arab Emirates'),
            self::USD => __('US Dollar - Official currency of the United States'),
            self::SGD => __('Singapore Dollar - Official currency of Singapore'),
        };
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
