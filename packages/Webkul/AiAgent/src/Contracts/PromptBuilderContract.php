<?php

namespace Webkul\AiAgent\Contracts;

use Webkul\AiAgent\DTOs\AgentPayload;

/**
 * Contract for building prompts sent to the AI provider.
 */
interface PromptBuilderContract
{
    /**
     * Build a prompt array from the given payload.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function build(AgentPayload $payload): array;
}
