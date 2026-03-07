<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Commands\ApiKey\CreateApiKeyCommand\CreateApiKeyCommand;
use App\Http\Commands\ApiKey\CreateApiKeyCommand\CreateApiKeyCommandHandler;
use App\Http\Commands\ApiKey\DeleteApiKeyCommand\DeleteApiKeyCommand;
use App\Http\Commands\ApiKey\DeleteApiKeyCommand\DeleteApiKeyCommandHandler;
use App\Http\Queries\ApiKey\GetApiKeysQuery\GetApiKeysQuery;
use App\Http\Queries\ApiKey\GetApiKeysQuery\GetApiKeysQueryHandler;
use App\Http\Requests\Api\V1\CreateApiKeyRequest;
use Illuminate\Http\JsonResponse;

class ApiKeyController extends Controller
{
    public function __construct(
        private readonly GetApiKeysQueryHandler $getApiKeysQueryHandler,
        private readonly CreateApiKeyCommandHandler $createApiKeyCommandHandler,
        private readonly DeleteApiKeyCommandHandler $deleteApiKeyCommandHandler
    ) {}

    public function index(): JsonResponse
    {
        $query = new GetApiKeysQuery(
            userId: request()->user()->id
        );

        return $this->getApiKeysQueryHandler->handle($query)->toResponse();
    }

    public function store(CreateApiKeyRequest $request): JsonResponse
    {
        $command = new CreateApiKeyCommand(
            userId: $request->user()->id,
            data: $request->validated()
        );

        return $this->createApiKeyCommandHandler->handle($command)->toResponse();
    }

    public function destroy(string $uuid): JsonResponse
    {
        $command = new DeleteApiKeyCommand(
            uuid: $uuid,
            userId: request()->user()->id
        );

        return $this->deleteApiKeyCommandHandler->handle($command)->toResponse();
    }
}
