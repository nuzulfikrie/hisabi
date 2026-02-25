<?php

namespace App\Http\Queries\Tag\GetAllTagsQuery;

use App\Domains\Tag\Services\TagService;
use Illuminate\Support\Facades\Auth;

class GetAllTagsQueryHandler
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    public function handle(GetAllTagsQuery $query): GetAllTagsQueryResponse
    {
        $tags = $this->tagService->getAll(Auth::id());

        return new GetAllTagsQueryResponse($tags);
    }
}
