<?php

namespace App\Http\Queries\SmsParser\GetSmsParserRulesQuery;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class GetSmsParserRulesQueryResponse
{
    public function __construct(
        private readonly Collection $rules
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'rules' => $this->rules->map(fn ($rule) => [
                'uuid' => $rule->uuid,
                'name' => $rule->name,
                'bank_name' => $rule->bank_name,
                'pattern' => $rule->pattern,
                'is_active' => $rule->is_active,
                'created_at' => $rule->created_at->toISOString(),
            ])
        ]);
    }
}
