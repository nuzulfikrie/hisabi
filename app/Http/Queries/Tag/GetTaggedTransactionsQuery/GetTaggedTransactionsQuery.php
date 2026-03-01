<?php

namespace App\Http\Queries\Tag\GetTaggedTransactionsQuery;

readonly class GetTaggedTransactionsQuery
{
    public function __construct(
        public string $uuid,
        public int $perPage = 50
    ) {}
}
