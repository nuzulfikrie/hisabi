<?php

namespace App\Http\Commands\ApiKey\CreateApiKeyCommand;

readonly class CreateApiKeyCommand
{
    public function __construct(
        public int $userId,
        public array $data
    ) {}
}
