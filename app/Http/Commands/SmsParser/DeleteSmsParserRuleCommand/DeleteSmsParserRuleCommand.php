<?php

namespace App\Http\Commands\SmsParser\DeleteSmsParserRuleCommand;

readonly class DeleteSmsParserRuleCommand
{
    public function __construct(
        public string $uuid,
        public int $userId
    ) {}
}
