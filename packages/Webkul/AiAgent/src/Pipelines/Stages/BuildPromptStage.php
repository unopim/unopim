<?php

namespace Webkul\AiAgent\Pipelines\Stages;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\Contracts\PromptBuilderContract;
use Webkul\AiAgent\DTOs\AgentPayload;

/**
 * Builds the message array for the AI API call by combining
 * the system prompt, context, and user instruction.
 */
class BuildPromptStage implements PipelineStageContract
{
    public function __construct(
        protected PromptBuilderContract $promptBuilder,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $messages = $this->promptBuilder->build($payload);

        $enriched = $payload->withMessages($messages);

        return $next($enriched);
    }
}
