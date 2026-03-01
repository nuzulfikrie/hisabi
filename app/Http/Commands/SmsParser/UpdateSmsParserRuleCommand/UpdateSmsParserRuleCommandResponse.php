<?php

namespace App\Http\Commands\SmsParser\UpdateSmsParserRuleCommand;

use App\Domains\SmsParser\Models\SmsParserRule;
use Illuminate\Http\JsonResponse;

class UpdateSmsParserRuleCommandResponse
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
                'bank_name' => $this->rule->bank_name,
                'pattern' => $this->rule->pattern,
                'is_active' => $this->rule->is_active,
                'created_at' => $this->rule->created_at->toISOString(),
            ]
        ]);
    }
}
