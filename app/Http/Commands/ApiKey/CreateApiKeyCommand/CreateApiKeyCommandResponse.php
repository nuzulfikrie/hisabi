<?php

namespace App\Http\Commands\ApiKey\CreateApiKeyCommand;

use App\Domains\ApiKey\Models\ApiKey;
use Illuminate\Http\JsonResponse;

class CreateApiKeyCommandResponse
{
    public function __construct(
        private readonly ApiKey $apiKey
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'apiKey' => [
                'uuid' => $this->apiKey->uuid,
                'name' => $this->apiKey->name,
                'key' => $this->apiKey->key, // Only shown once on creation
                'created_at' => $this->apiKey->created_at->toISOString(),
                'last_used_at' => $this->apiKey->last_used_at?->toISOString(),
            ]
        ], 201);
    }
}
