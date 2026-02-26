<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Commands\Tag\CreateTagCommand\CreateTagCommand;
use App\Http\Commands\Tag\CreateTagCommand\CreateTagCommandHandler;
use App\Http\Commands\Tag\UpdateTagCommand\UpdateTagCommand;
use App\Http\Commands\Tag\UpdateTagCommand\UpdateTagCommandHandler;
use App\Http\Commands\Tag\DeleteTagCommand\DeleteTagCommand;
use App\Http\Commands\Tag\DeleteTagCommand\DeleteTagCommandHandler;
use App\Http\Queries\Tag\GetTagsQuery\GetTagsQuery;
use App\Http\Queries\Tag\GetTagsQuery\GetTagsQueryHandler;
use App\Http\Queries\Tag\GetAllTagsQuery\GetAllTagsQuery;
use App\Http\Queries\Tag\GetAllTagsQuery\GetAllTagsQueryHandler;
use App\Http\Queries\Tag\GetTaggedTransactionsQuery\GetTaggedTransactionsQuery;
use App\Http\Queries\Tag\GetTaggedTransactionsQuery\GetTaggedTransactionsQueryHandler;
use App\Http\Requests\Api\V1\CreateTagRequest;
use App\Http\Requests\Api\V1\UpdateTagRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    public function __construct(
        private readonly GetTagsQueryHandler $getTagsQueryHandler,
        private readonly GetAllTagsQueryHandler $getAllTagsQueryHandler,
        private readonly CreateTagCommandHandler $createTagCommandHandler,
        private readonly UpdateTagCommandHandler $updateTagCommandHandler,
        private readonly DeleteTagCommandHandler $deleteTagCommandHandler,
        private readonly GetTaggedTransactionsQueryHandler $getTaggedTransactionsQueryHandler
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetTagsQuery(
            perPage: (int) $request->get('perPage', 50)
        );

        return $this->getTagsQueryHandler->handle($query)->toResponse();
    }

    public function all(): JsonResponse
    {
        $query = new GetAllTagsQuery();

        return $this->getAllTagsQueryHandler->handle($query)->toResponse();
    }

    public function store(CreateTagRequest $request): JsonResponse
    {
        $command = new CreateTagCommand(
            data: $request->validated(),
            userId: Auth::id()
        );

        return $this->createTagCommandHandler->handle($command)->toResponse();
    }

    public function update(UpdateTagRequest $request, string $uuid): JsonResponse
    {
        $command = new UpdateTagCommand(
            uuid: $uuid,
            data: $request->validated(),
            userId: Auth::id()
        );

        return $this->updateTagCommandHandler->handle($command)->toResponse();
    }

    public function destroy(string $uuid): JsonResponse
    {
        $command = new DeleteTagCommand(
            uuid: $uuid,
            userId: Auth::id()
        );

        return $this->deleteTagCommandHandler->handle($command)->toResponse();
    }

    public function transactions(Request $request, string $uuid): JsonResponse
    {
        $query = new GetTaggedTransactionsQuery(
            uuid: $uuid,
            perPage: (int) $request->get('perPage', 50)
        );

        return $this->getTaggedTransactionsQueryHandler->handle($query)->toResponse();
    }
}
