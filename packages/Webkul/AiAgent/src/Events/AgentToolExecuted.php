<?php

namespace Webkul\AiAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Webkul\AiAgent\Chat\ChatContext;

/**
 * Dispatched for each tool call observed on a completed agent turn.
 *
 * laravel/ai executes tools internally with no per-call hook, so this event
 * fires after the agent loop finishes, once per tool result — suitable for
 * auditing/metrics, not for vetoing execution.
 */
class AgentToolExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly string $toolName,
        public readonly array $arguments,
        public readonly mixed $result,
        public readonly ?ChatContext $context = null,
    ) {}
}
