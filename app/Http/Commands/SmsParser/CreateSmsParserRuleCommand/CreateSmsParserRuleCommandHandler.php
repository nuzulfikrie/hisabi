<?php

namespace App\Http\Commands\SmsParser\CreateSmsParserRuleCommand;

use App\Domains\SmsParser\Services\SmsParserService;

class CreateSmsParserRuleCommandHandler
{
    public function __construct(
        private readonly SmsParserService $smsParserService
    ) {}

    public function handle(CreateSmsParserRuleCommand $command): CreateSmsParserRuleCommandResponse
    {
        $rule = $this->smsParserService->create($command->data, $command->userId);
        return new CreateSmsParserRuleCommandResponse($rule);
    }
}
