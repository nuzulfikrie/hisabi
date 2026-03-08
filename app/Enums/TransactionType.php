<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum TransactionType: string implements Enum
{
    use InteractsWithEnum;

    case INCOME = 'income';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::INCOME => __('Income'),
            self::EXPENSE => __('Expense'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INCOME => __('Money coming in'),
            self::EXPENSE => __('Money going out'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::INCOME => 'success',
            self::EXPENSE => 'danger',
        };
    }

    public function sign(): string
    {
        return match ($this) {
            self::INCOME => '+',
            self::EXPENSE => '-',
        };
    }
}
