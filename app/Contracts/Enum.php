<?php

declare(strict_types=1);

namespace App\Contracts;

interface Enum
{
    /**
     * Get the label for the enum case.
     */
    public function label(): string;

    /**
     * Get the description for the enum case.
     */
    public function description(): string;
}
