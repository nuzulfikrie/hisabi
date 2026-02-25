<?php

namespace App\Http\Queries\Tag\GetTagsQuery;

use App\Domains\Tag\Services\TagService;
use Illuminate\Support\Facades\Auth;

class GetTagsQueryHandler
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    public function handle(GetTagsQuery $query): GetTagsQueryResponse
    {
        $tags = $this->tagService->getPaginated(
            Auth::id(),
            $query->perPage
        );

        return new GetTagsQueryResponse($tags);
    }
}
