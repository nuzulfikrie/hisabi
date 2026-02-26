<?php

namespace App\Http\Commands\SmsParser\DeleteSmsParserRuleCommand;

use App\Domains\SmsParser\Services\SmsParserService;

class DeleteSmsParserRuleCommandHandler
{
    public function __construct(
        private readonly SmsParserService $smsParserService
    ) {}

    public function handle(DeleteSmsParserRuleCommand $command): DeleteSmsParserRuleCommandResponse
    {
        $rule = $this->smsParserService->delete(
            $command->uuid,
            $command->userId
        );

        return new DeleteSmsParserRuleCommandResponse($rule);
    }
}
