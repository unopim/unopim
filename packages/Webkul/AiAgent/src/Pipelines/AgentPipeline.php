<?php

namespace Webkul\AiAgent\Pipelines;

use Illuminate\Pipeline\Pipeline;
use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;

/**
 * Orchestrates the agent execution pipeline.
 *
 * Dynamically resolves pipeline stage classes from the agent config
 * and passes the AgentPayload through each stage sequentially.
 */
class AgentPipeline
{
    /**
     * @var array<class-string<PipelineStageContract>>
     */
    protected array $stages = [];

    /**
     * Set the pipeline stages.
     *
     * @param  array<class-string<PipelineStageContract>>  $stages
     */
    public function through(array $stages): static
    {
        $this->stages = $stages;

        return $this;
    }

    /**
     * Process the payload through all pipeline stages.
     */
    public function process(AgentPayload $payload): AgentPayload
    {
        return app(Pipeline::class)
            ->send($payload)
            ->through($this->stages)
            ->via('handle')
            ->thenReturn();
    }
}
