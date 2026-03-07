<?php

namespace App\Http\Queries\User\GetUserPreferencesQuery;

readonly class GetUserPreferencesQuery
{
    public function __construct(
        public int $userId
    ) {}
}
