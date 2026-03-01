<?php

namespace App\Http\Commands\SmsParser\DeleteSmsParserRuleCommand;

use App\Domains\SmsParser\Models\SmsParserRule;
use Illuminate\Http\JsonResponse;

class DeleteSmsParserRuleCommandResponse
{
    public function __construct(
        private readonly SmsParserRule $rule
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'rule' => [
                'uuid' => $this->rule->uuid,
                'name' => $this->rule->name,
            ]
        ]);
    }
}
