<?php

namespace App\Http\Queries\ApiKey\GetApiKeysQuery;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class GetApiKeysQueryResponse
{
    public function __construct(
        private readonly Collection $apiKeys
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'apiKeys' => $this->apiKeys->map(fn ($key) => [
                'uuid' => $key->uuid,
                'name' => $key->name,
                'created_at' => $key->created_at->toISOString(),
                'last_used_at' => $key->last_used_at?->toISOString(),
            ])
        ]);
    }
}
