<?php

namespace App\Http\Queries\Tag\GetTaggedTransactionsQuery;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

readonly class GetTaggedTransactionsQueryResponse
{
    public function __construct(
        private LengthAwarePaginator $transactions
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'data' => $this->transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'note' => $transaction->note,
                    'created_at' => $transaction->created_at,
                    'brand' => $transaction->brand ? [
                        'id' => $transaction->brand->id,
                        'name' => $transaction->brand->name,
                    ] : null,
                    'category' => $transaction->brand && $transaction->brand->category ? [
                        'id' => $transaction->brand->category->id,
                        'name' => $transaction->brand->category->name,
                        'color' => $transaction->brand->category->color,
                    ] : null,
                ];
            }),
            'paginatorInfo' => [
                'hasMorePages' => $this->transactions->hasMorePages(),
                'currentPage' => $this->transactions->currentPage(),
                'lastPage' => $this->transactions->lastPage(),
                'perPage' => $this->transactions->perPage(),
                'total' => $this->transactions->total(),
            ],
        ]);
    }
}
