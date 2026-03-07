<?php

namespace App\Http\Commands\ApiKey\DeleteApiKeyCommand;

readonly class DeleteApiKeyCommand
{
    public function __construct(
        public string $uuid,
        public int $userId
    ) {}
}
