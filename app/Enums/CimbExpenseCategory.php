<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum CimbExpenseCategory: string implements Enum
{
    use InteractsWithEnum;

    case TNG = 'tng';
    case SHOPEE_PAY = 'shopee_pay';
    case SHOPEE = 'shopee';
    case WITHDRAW = 'withdraw';
    case SERVICES_FEE = 'services_fee';
    case FOODPANDA = 'foodpanda';
    case HOTLINK = 'hotlink';
    case GRAB = 'grab';
    case VERSA = 'versa';
    case UNCATEGORIZED = 'uncategorized';

    public function label(): string
    {
        return match ($this) {
            self::TNG => __('TNG'),
            self::SHOPEE_PAY => __('ShopeePay'),
            self::SHOPEE => __('Shopee'),
            self::WITHDRAW => __('Withdraw'),
            self::SERVICES_FEE => __('Services/Fee'),
            self::FOODPANDA => __('Foodpanda'),
            self::HOTLINK => __('Hotlink'),
            self::GRAB => __('Grab'),
            self::VERSA => __('Versa'),
            self::UNCATEGORIZED => __('Uncategorized'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TNG => __('Touch \'n Go e-wallet transactions'),
            self::SHOPEE_PAY => __('ShopeePay wallet top-ups and payments'),
            self::SHOPEE => __('Shopee purchases'),
            self::WITHDRAW => __('ATM withdrawals'),
            self::SERVICES_FEE => __('Banking services and card fees'),
            self::FOODPANDA => __('Foodpanda orders'),
            self::HOTLINK => __('Hotlink mobile top-ups'),
            self::GRAB => __('Grab services and payments'),
            self::VERSA => __('Versa/Aham Asset investments'),
            self::UNCATEGORIZED => __('Uncategorized expenses'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::TNG => 'blue',
            self::SHOPEE_PAY => 'orange',
            self::SHOPEE => 'red',
            self::WITHDRAW => 'gray',
            self::SERVICES_FEE => 'yellow',
            self::FOODPANDA => 'pink',
            self::HOTLINK => 'purple',
            self::GRAB => 'green',
            self::VERSA => 'indigo',
            self::UNCATEGORIZED => 'default',
        };
    }

    /**
     * Get the keywords used to identify this category from description.
     *
     * @return array<int, string>
     */
    public function keywords(): array
    {
        return match ($this) {
            self::TNG => ['hktm-principal', 'tng'],
            self::SHOPEE_PAY => ['banking/wallets', 'shopee top up', 'shopeepay'],
            self::SHOPEE => ['shopee'],
            self::WITHDRAW => ['atm withdrawal'],
            self::SERVICES_FEE => ['atm/debit card fee'],
            self::FOODPANDA => ['foodpanda'],
            self::HOTLINK => ['hotlink', 'shopee mobile'],
            self::GRAB => ['gpay'],
            self::VERSA => ['aham asset'],
            self::UNCATEGORIZED => [],
        };
    }

    /**
     * Try to match a description to a category.
     */
    public static function match(string $description): self
    {
        $descriptionLower = strtolower($description);

        foreach (self::cases() as $case) {
            if ($case === self::UNCATEGORIZED) {
                continue;
            }

            foreach ($case->keywords() as $keyword) {
                if (str_contains($descriptionLower, $keyword)) {
                    return $case;
                }
            }
        }

        return self::UNCATEGORIZED;
    }
}
