<?php

namespace App\Http\Commands\User\UpdateUserPreferencesCommand;

readonly class UpdateUserPreferencesCommand
{
    public function __construct(
        public int $userId,
        public array $data
    ) {}
}
