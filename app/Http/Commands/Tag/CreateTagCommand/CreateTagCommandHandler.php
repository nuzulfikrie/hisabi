<?php

namespace App\Http\Commands\Tag\CreateTagCommand;

use App\Domains\Tag\Services\TagService;
use Illuminate\Support\Facades\DB;

class CreateTagCommandHandler
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    public function handle(CreateTagCommand $command): CreateTagCommandResponse
    {
        return DB::transaction(function () use ($command) {
            $tag = $this->tagService->create($command->data, $command->userId);
            return new CreateTagCommandResponse($tag);
        });
    }
}
