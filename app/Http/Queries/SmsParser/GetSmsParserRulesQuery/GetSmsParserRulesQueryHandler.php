<?php

namespace App\Http\Queries\SmsParser\GetSmsParserRulesQuery;

use App\Domains\SmsParser\Services\SmsParserService;

class GetSmsParserRulesQueryHandler
{
    public function __construct(
        private readonly SmsParserService $smsParserService
    ) {}

    public function handle(GetSmsParserRulesQuery $query): GetSmsParserRulesQueryResponse
    {
        $rules = $this->smsParserService->getAllForUser($query->userId);
        return new GetSmsParserRulesQueryResponse($rules);
    }
}
