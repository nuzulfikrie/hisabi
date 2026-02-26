<?php

namespace App\Http\Commands\Tag\UpdateTagCommand;

use App\Domains\Tag\Services\TagService;
use Illuminate\Support\Facades\DB;

class UpdateTagCommandHandler
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    public function handle(UpdateTagCommand $command): UpdateTagCommandResponse
    {
        return DB::transaction(function () use ($command) {
            $tag = $this->tagService->update($command->uuid, $command->data, $command->userId);
            return new UpdateTagCommandResponse($tag);
        });
    }
}
