<?php

namespace App\Http\Queries\Tag\GetAllTagsQuery;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

readonly class GetAllTagsQueryResponse
{
    public function __construct(
        private Collection $tags
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => $this->tags->map(function ($tag) {
                return [
                    'uuid' => $tag->uuid,
                    'name' => $tag->name,
                    'color' => $tag->color,
                    'transactionsCount' => $tag->transactions_count ?? 0,
                ];
            }),
        ]);
    }
}
