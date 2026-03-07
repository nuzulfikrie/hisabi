<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Commands\User\UpdateUserPreferencesCommand\UpdateUserPreferencesCommand;
use App\Http\Commands\User\UpdateUserPreferencesCommand\UpdateUserPreferencesCommandHandler;
use App\Http\Queries\User\GetUserPreferencesQuery\GetUserPreferencesQuery;
use App\Http\Queries\User\GetUserPreferencesQuery\GetUserPreferencesQueryHandler;
use App\Http\Requests\Api\V1\UpdateUserPreferencesRequest;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly GetUserPreferencesQueryHandler $getUserPreferencesQueryHandler,
        private readonly UpdateUserPreferencesCommandHandler $updateUserPreferencesCommandHandler
    ) {}

    public function getPreferences(): JsonResponse
    {
        $query = new GetUserPreferencesQuery(
            userId: request()->user()->id
        );

        return $this->getUserPreferencesQueryHandler->handle($query)->toResponse();
    }

    public function updatePreferences(UpdateUserPreferencesRequest $request): JsonResponse
    {
        $command = new UpdateUserPreferencesCommand(
            userId: $request->user()->id,
            data: $request->validated()
        );

        return $this->updateUserPreferencesCommandHandler->handle($command)->toResponse();
    }
}
