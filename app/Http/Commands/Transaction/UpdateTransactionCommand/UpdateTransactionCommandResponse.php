<?php

namespace App\Http\Commands\Transaction\UpdateTransactionCommand;

use App\Domains\Transaction\Models\Transaction;
use Illuminate\Http\JsonResponse;

readonly class UpdateTransactionCommandResponse
{
    public function __construct(
        private Transaction $transaction
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'transaction' => [
                'id' => $this->transaction->id,
                'amount' => $this->transaction->amount,
                'note' => $this->transaction->note,
                'created_at' => $this->transaction->created_at,
                'brand' => $this->transaction->brand ? [
                    'id' => $this->transaction->brand->id,
                    'name' => $this->transaction->brand->name,
                ] : null,
                'category' => $this->transaction->brand && $this->transaction->brand->category ? [
                    'id' => $this->transaction->brand->category->id,
                    'name' => $this->transaction->brand->category->name,
                    'color' => $this->transaction->brand->category->color,
                ] : null,
                'tags' => $this->transaction->tags ? $this->transaction->tags->map(function ($tag) {
                    return [
                        'uuid' => $tag->uuid,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ];
                }) : [],
            ],
        ]);
    }
}
