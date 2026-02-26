<?php

namespace App\Http\Commands\Tag\CreateTagCommand;

readonly class CreateTagCommand
{
    public function __construct(
        public array $data,
        public int $userId
    ) {}
}
