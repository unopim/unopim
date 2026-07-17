<?php

namespace Webkul\AiAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Webkul\AiAgent\Chat\ChatContext;

/**
 * Dispatched after the agent system prompt has been assembled and before it
 * is handed to the LLM. Listeners may mutate the public $prompt property to
 * append or rewrite instructions (e.g. plugin-specific behavior rules).
 */
class AgentSystemPromptBuilding
{
    use Dispatchable;

    public function __construct(
        public string $prompt,
        public readonly ChatContext $context,
    ) {}
}
