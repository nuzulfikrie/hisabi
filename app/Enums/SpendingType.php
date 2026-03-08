<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum SpendingType: string implements Enum
{
    use InteractsWithEnum;

    case HOME = 'home';
    case PERSONAL = 'personal';

    public function label(): string
    {
        return match ($this) {
            self::HOME => __('Home'),
            self::PERSONAL => __('Personal'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::HOME => 'primary',
            self::PERSONAL => 'secondary',
        };
    }
}
