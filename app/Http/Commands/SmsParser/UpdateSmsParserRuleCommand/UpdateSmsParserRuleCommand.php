<?php

namespace App\Http\Commands\SmsParser\UpdateSmsParserRuleCommand;

readonly class UpdateSmsParserRuleCommand
{
    public function __construct(
        public string $uuid,
        public int $userId,
        public array $data
    ) {}
}
