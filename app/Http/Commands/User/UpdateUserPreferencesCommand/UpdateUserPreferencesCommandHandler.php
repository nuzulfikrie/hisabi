<?php

namespace App\Http\Commands\User\UpdateUserPreferencesCommand;

use App\Domains\User\Services\UserPreferenceService;

class UpdateUserPreferencesCommandHandler
{
    public function __construct(
        private readonly UserPreferenceService $userPreferenceService
    ) {}

    public function handle(UpdateUserPreferencesCommand $command): UpdateUserPreferencesCommandResponse
    {
        $preferences = $this->userPreferenceService->update(
            $command->userId,
            $command->data
        );

        return new UpdateUserPreferencesCommandResponse($preferences);
    }
}
