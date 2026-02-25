<?php

namespace App\Http\Queries\ApiKey\GetApiKeysQuery;

use App\Domains\ApiKey\Services\ApiKeyService;

class GetApiKeysQueryHandler
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {}

    public function handle(GetApiKeysQuery $query): GetApiKeysQueryResponse
    {
        $apiKeys = $this->apiKeyService->getAllForUser($query->userId);
        return new GetApiKeysQueryResponse($apiKeys);
    }
}
