<?php

namespace App\Http\Commands\ApiKey\DeleteApiKeyCommand;

use App\Domains\ApiKey\Models\ApiKey;
use Illuminate\Http\JsonResponse;

class DeleteApiKeyCommandResponse
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
            ]
        ]);
    }
}
