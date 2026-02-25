<?php

namespace App\Http\Queries\Tag\GetTaggedTransactionsQuery;

use App\Domains\Tag\Services\TagService;
use Illuminate\Support\Facades\Auth;

class GetTaggedTransactionsQueryHandler
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    public function handle(GetTaggedTransactionsQuery $query): GetTaggedTransactionsQueryResponse
    {
        $transactions = $this->tagService->getTaggedTransactions(
            $query->uuid,
            Auth::id(),
            $query->perPage
        );

        return new GetTaggedTransactionsQueryResponse($transactions);
    }
}
