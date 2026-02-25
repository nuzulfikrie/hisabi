<?php

namespace App\Http\Commands\Tag\DeleteTagCommand;

readonly class DeleteTagCommand
{
    public function __construct(
        public string $uuid,
        public int $userId
    ) {}
}
