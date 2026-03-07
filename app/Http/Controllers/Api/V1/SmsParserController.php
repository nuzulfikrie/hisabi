<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Commands\SmsParser\CreateSmsParserRuleCommand\CreateSmsParserRuleCommand;
use App\Http\Commands\SmsParser\CreateSmsParserRuleCommand\CreateSmsParserRuleCommandHandler;
use App\Http\Commands\SmsParser\UpdateSmsParserRuleCommand\UpdateSmsParserRuleCommand;
use App\Http\Commands\SmsParser\UpdateSmsParserRuleCommand\UpdateSmsParserRuleCommandHandler;
use App\Http\Commands\SmsParser\DeleteSmsParserRuleCommand\DeleteSmsParserRuleCommand;
use App\Http\Commands\SmsParser\DeleteSmsParserRuleCommand\DeleteSmsParserRuleCommandHandler;
use App\Http\Queries\SmsParser\GetSmsParserRulesQuery\GetSmsParserRulesQuery;
use App\Http\Queries\SmsParser\GetSmsParserRulesQuery\GetSmsParserRulesQueryHandler;
use App\Http\Requests\Api\V1\CreateSmsParserRuleRequest;
use App\Http\Requests\Api\V1\UpdateSmsParserRuleRequest;
use App\Http\Requests\Api\V1\TestSmsParserRequest;
use App\Domains\SmsParser\Services\SmsParserService;
use Illuminate\Http\JsonResponse;

class SmsParserController extends Controller
{
    public function __construct(
        private readonly GetSmsParserRulesQueryHandler $getSmsParserRulesQueryHandler,
        private readonly CreateSmsParserRuleCommandHandler $createSmsParserRuleCommandHandler,
        private readonly UpdateSmsParserRuleCommandHandler $updateSmsParserRuleCommandHandler,
        private readonly DeleteSmsParserRuleCommandHandler $deleteSmsParserRuleCommandHandler,
        private readonly SmsParserService $smsParserService
    ) {}

    public function index(): JsonResponse
    {
        $query = new GetSmsParserRulesQuery(
            userId: request()->user()->id
        );

        return $this->getSmsParserRulesQueryHandler->handle($query)->toResponse();
    }

    public function store(CreateSmsParserRuleRequest $request): JsonResponse
    {
        $command = new CreateSmsParserRuleCommand(
            userId: $request->user()->id,
            data: $request->validated()
        );

        return $this->createSmsParserRuleCommandHandler->handle($command)->toResponse();
    }

    public function update(UpdateSmsParserRuleRequest $request, string $uuid): JsonResponse
    {
        $command = new UpdateSmsParserRuleCommand(
            uuid: $uuid,
            userId: $request->user()->id,
            data: $request->validated()
        );

        return $this->updateSmsParserRuleCommandHandler->handle($command)->toResponse();
    }

    public function destroy(string $uuid): JsonResponse
    {
        $command = new DeleteSmsParserRuleCommand(
            uuid: $uuid,
            userId: request()->user()->id
        );

        return $this->deleteSmsParserRuleCommandHandler->handle($command)->toResponse();
    }

    public function test(TestSmsParserRequest $request): JsonResponse
    {
        $result = $this->smsParserService->test(
            $request->input('sms'),
            $request->input('pattern')
        );

        return response()->json($result);
    }
}
