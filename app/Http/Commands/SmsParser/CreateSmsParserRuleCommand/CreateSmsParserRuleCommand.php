<?php

namespace App\Http\Commands\SmsParser\CreateSmsParserRuleCommand;

readonly class CreateSmsParserRuleCommand
{
    public function __construct(
        public int $userId,
        public array $data
    ) {}
}
