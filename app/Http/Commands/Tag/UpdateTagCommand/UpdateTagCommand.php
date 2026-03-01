<?php

namespace App\Http\Commands\Tag\UpdateTagCommand;

readonly class UpdateTagCommand
{
    public function __construct(
        public string $uuid,
        public array $data,
        public int $userId
    ) {}
}
