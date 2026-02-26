<?php

namespace App\Http\Commands\Tag\UpdateTagCommand;

use App\Domains\Tag\Models\Tag;
use Illuminate\Http\JsonResponse;

readonly class UpdateTagCommandResponse
{
    public function __construct(
        private Tag $tag
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'tag' => [
                'uuid' => $this->tag->uuid,
                'name' => $this->tag->name,
                'color' => $this->tag->color,
                'transactionsCount' => $this->tag->transactions_count ?? 0,
            ],
        ]);
    }
}
