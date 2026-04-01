<?php

namespace Webkul\AiAgent\Pipelines\Stages;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\Repositories\AgentExecutionRepository;

/**
 * Final pipeline stage: persists the execution result to the database.
 */
class LogExecutionStage implements PipelineStageContract
{
    public function __construct(
        protected AgentExecutionRepository $executionRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $executionId = $payload->metadata['executionId'] ?? null;

        if ($executionId) {
            $this->executionRepository->markCompleted(
                id: $executionId,
                output: $payload->metadata['aiResponse'] ?? '',
                tokensUsed: $payload->metadata['tokensUsed'] ?? 0,
                executionTimeMs: $payload->metadata['executionTimeMs'] ?? 0,
            );
        }

        return $next($payload);
    }
}
