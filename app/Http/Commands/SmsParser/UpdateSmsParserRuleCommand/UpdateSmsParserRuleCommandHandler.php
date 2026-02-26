<?php

namespace App\Http\Commands\SmsParser\UpdateSmsParserRuleCommand;

use App\Domains\SmsParser\Services\SmsParserService;

class UpdateSmsParserRuleCommandHandler
{
    public function __construct(
        private readonly SmsParserService $smsParserService
    ) {}

    public function handle(UpdateSmsParserRuleCommand $command): UpdateSmsParserRuleCommandResponse
    {
        $rule = $this->smsParserService->update(
            $command->uuid,
            $command->data,
            $command->userId
        );

        return new UpdateSmsParserRuleCommandResponse($rule);
    }
}
