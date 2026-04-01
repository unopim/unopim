<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\Http\Requests\ExecuteAgentForm;
use Webkul\AiAgent\Services\AgentService;

class ExecutionController extends Controller
{
    public function __construct(
        protected AgentService $agentService,
    ) {}

    /**
     * Execute an agent (synchronously or via queue).
     */
    public function execute(ExecuteAgentForm $request): JsonResponse
    {
        $data = $request->validated();

        $payload = new AgentPayload(
            agentId: $data['agentId'],
            credentialId: $data['credentialId'],
            instruction: $data['instruction'],
            context: $data['context'] ?? [],
        );

        if ($data['async'] ?? false) {
            $this->agentService->executeAsync($payload);

            return new JsonResponse([
                'message' => trans('ai-agent::app.executions.queued'),
            ]);
        }

        $result = $this->agentService->execute($payload);

        return new JsonResponse($result->toArray());
    }
}
