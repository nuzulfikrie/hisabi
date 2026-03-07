<?php

namespace App\Http\Commands\ApiKey\DeleteApiKeyCommand;

use App\Domains\ApiKey\Services\ApiKeyService;

class DeleteApiKeyCommandHandler
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService
    ) {}

    public function handle(DeleteApiKeyCommand $command): DeleteApiKeyCommandResponse
    {
        $apiKey = $this->apiKeyService->delete(
            $command->uuid,
            $command->userId
        );

        return new DeleteApiKeyCommandResponse($apiKey);
    }
}
