<?php

namespace App\Http\Queries\Tag\GetTagsQuery;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

readonly class GetTagsQueryResponse
{
    public function __construct(
        private LengthAwarePaginator $tags
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
            'paginatorInfo' => [
                'hasMorePages' => $this->tags->hasMorePages(),
                'currentPage' => $this->tags->currentPage(),
                'lastPage' => $this->tags->lastPage(),
                'perPage' => $this->tags->perPage(),
                'total' => $this->tags->total(),
            ],
        ]);
    }
}
