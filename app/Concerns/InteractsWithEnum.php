<?php

declare(strict_types=1);

namespace App\Concerns;

trait InteractsWithEnum
{
    /**
     * Get all enum cases as array with value, label, and description.
     *
     * @return array<int, array<string, string>>
     */
    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
        ], self::cases());
    }

    /**
     * Get enum options for dropdowns (value and label only).
     *
     * @return array<int, array<string, string>>
     */
    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    /**
     * Get all enum values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
