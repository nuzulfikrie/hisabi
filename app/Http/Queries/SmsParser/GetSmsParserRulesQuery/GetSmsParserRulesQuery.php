<?php

namespace App\Http\Queries\SmsParser\GetSmsParserRulesQuery;

readonly class GetSmsParserRulesQuery
{
    public function __construct(
        public int $userId
    ) {}
}
