<?php

namespace Webkul\AiAgent\Contracts;

use Webkul\AiAgent\DTOs\AgentPayload;

/**
 * Contract for all pipeline stages.
 *
 * Each stage receives the payload, performs work,
 * and passes it to the next stage via the closure.
 */
interface PipelineStageContract
{
    /**
     * Handle the payload and pass to the next stage.
     *
     * @param  \Closure(AgentPayload): AgentPayload  $next
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload;
}
