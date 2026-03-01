<?php

namespace App\Http\Commands\ApiKey\CreateApiKeyCommand;

use App\Domains\ApiKey\Services\ApiKeyService;

class CreateApiKeyCommandHandler
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {}

    public function handle(CreateApiKeyCommand $command): CreateApiKeyCommandResponse
    {
        $apiKey = $this->apiKeyService->create(
            ['name' => $command->data['name']],
            $command->userId
        );

        return new CreateApiKeyCommandResponse($apiKey);
    }
}
