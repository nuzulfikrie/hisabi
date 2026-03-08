<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum UserRole: string implements Enum
{
    use InteractsWithEnum;

    case ADMIN = 'admin';
    case USER = 'user';
    case ACCOUNTANT = 'accountant';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => __('Administrator'),
            self::USER => __('User'),
            self::ACCOUNTANT => __('Accountant'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ADMIN => __('Full system access and management'),
            self::USER => __('Regular user with limited access'),
            self::ACCOUNTANT => __('Financial reporting and analysis access'),
        };
    }

    public static function default(): self
    {
        return self::USER;
    }
}
