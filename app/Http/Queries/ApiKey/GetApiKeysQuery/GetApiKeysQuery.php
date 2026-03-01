<?php

namespace App\Http\Queries\ApiKey\GetApiKeysQuery;

readonly class GetApiKeysQuery
{
    public function __construct(
        public int $userId
    ) {}
}
