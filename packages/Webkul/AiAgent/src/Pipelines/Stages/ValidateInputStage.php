<?php

namespace Webkul\AiAgent\Pipelines\Stages;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\Repositories\AgentRepository;

/**
 * First pipeline stage: validates the payload and enriches it
 * with the agent's system prompt from the database.
 */
class ValidateInputStage implements PipelineStageContract
{
    public function __construct(
        protected AgentRepository $agentRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $agent = $this->agentRepository->findOrFail($payload->agentId);

        if (! $agent->status) {
            throw new \RuntimeException('Agent is disabled: '.$agent->name);
        }

        $enriched = $payload->withMetadata([
            'agentName'    => $agent->name,
            'systemPrompt' => $agent->systemPrompt,
            'maxTokens'    => $agent->maxTokens,
            'temperature'  => $agent->temperature,
        ]);

        return $next($enriched);
    }
}
