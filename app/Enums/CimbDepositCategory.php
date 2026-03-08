<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\InteractsWithEnum;
use App\Contracts\Enum;

enum CimbDepositCategory: string implements Enum
{
    use InteractsWithEnum;

    case SALARY = 'salary';
    case SERVICES_FEES = 'services_fees';
    case CLAIMS = 'claims';
    case UNCATEGORIZED = 'uncategorized';

    public function label(): string
    {
        return match ($this) {
            self::SALARY => __('Salary'),
            self::SERVICES_FEES => __('Services/Fees'),
            self::CLAIMS => __('Claims'),
            self::UNCATEGORIZED => __('Uncategorized'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SALARY => __('Salary and income deposits'),
            self::SERVICES_FEES => __('Credit interest and banking services'),
            self::CLAIMS => __('Reimbursement claims'),
            self::UNCATEGORIZED => __('Uncategorized deposits'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::SALARY => 'green',
            self::SERVICES_FEES => 'blue',
            self::CLAIMS => 'purple',
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
            self::SALARY => ['salary'],
            self::SERVICES_FEES => ['credit interest'],
            self::CLAIMS => ['claim'],
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
