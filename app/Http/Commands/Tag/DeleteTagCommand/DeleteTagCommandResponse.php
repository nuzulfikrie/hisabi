<?php

namespace App\Http\Commands\Tag\DeleteTagCommand;

use App\Domains\Tag\Models\Tag;
use Illuminate\Http\JsonResponse;

readonly class DeleteTagCommandResponse
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
            ],
        ]);
    }
}
