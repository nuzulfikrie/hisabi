<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum UserStatus: string implements Enum
{
    use InteractsWithEnum;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('Active'),
            self::INACTIVE => __('Inactive'),
            self::SUSPENDED => __('Suspended'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => __('User is active and can access the system'),
            self::INACTIVE => __('User is inactive and cannot access the system'),
            self::SUSPENDED => __('User is temporarily suspended'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'danger',
            self::SUSPENDED => 'warning',
        };
    }

    public static function default(): self
    {
        return self::ACTIVE;
    }
}
