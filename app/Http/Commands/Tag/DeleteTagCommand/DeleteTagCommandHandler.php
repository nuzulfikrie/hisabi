<?php

namespace App\Http\Commands\Tag\DeleteTagCommand;

use App\Domains\Tag\Services\TagService;
use Illuminate\Support\Facades\DB;

class DeleteTagCommandHandler
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    public function handle(DeleteTagCommand $command): DeleteTagCommandResponse
    {
        return DB::transaction(function () use ($command) {
            $tag = $this->tagService->delete($command->uuid, $command->userId);
            return new DeleteTagCommandResponse($tag);
        });
    }
}
