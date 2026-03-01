<?php

namespace App\Http\Queries\User\GetUserPreferencesQuery;

use App\Domains\User\Services\UserPreferenceService;

class GetUserPreferencesQueryHandler
{
    public function __construct(
        private readonly UserPreferenceService $userPreferenceService
    ) {}

    public function handle(GetUserPreferencesQuery $query): GetUserPreferencesQueryResponse
    {
        $preferences = $this->userPreferenceService->getForUser($query->userId);

        return new GetUserPreferencesQueryResponse($preferences);
    }
}
