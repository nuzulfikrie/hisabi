<?php

namespace App\Http\Queries\Tag\GetTagsQuery;

class GetTagsQuery
{
    public function __construct(
        public int $perPage = 50
    ) {}
}
