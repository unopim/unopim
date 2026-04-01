<?php

namespace Webkul\AiAgent\Agents;

use Webkul\AiAgent\Contracts\AgentServiceContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\AgentResult;

/**
 * Abstract base class for concrete agent implementations.
 *
 * Provides common functionality for dependency injection,
 * payload building, and execution orchestration.
 *
 * Extend this class to create domain-specific agents:
 *   - ImageProductAgent
 *   - TextDescriptionAgent
 *   - BulkProductEnricherAgent
 */
abstract class BaseAgent
{
    public function __construct(
        protected AgentServiceContract $agentService,
    ) {}

    /**
     * Get the default system prompt for this agent.
     *
     * Override in subclasses to provide specialized behavior.
     */
    abstract protected function getDefaultSystemPrompt(): string;

    /**
     * Build the instruction for this agent from input.
     *
     * Override in subclasses to customize instruction formatting.
     */
    abstract protected function buildInstruction(mixed $input): string;

    /**
     * Execute the agent synchronously.
     *
     * @param  mixed  $input  Domain-specific input (image, text, array, etc.)
     * @param  int  $agentId  Agent configuration ID
     * @param  int  $credentialId  AI credential ID
     * @param  array<string, mixed>  $context  Extra context
     */
    public function execute(
        mixed $input,
        int $agentId,
        int $credentialId,
        array $context = [],
    ): AgentResult {
        $payload = new AgentPayload(
            agentId: $agentId,
            credentialId: $credentialId,
            instruction: $this->buildInstruction($input),
            context: $context,
        );

        return $this->agentService->execute($payload);
    }

    /**
     * Execute the agent asynchronously (queued).
     *
     * @param  array<string, mixed>  $context
     */
    public function executeAsync(
        mixed $input,
        int $agentId,
        int $credentialId,
        array $context = [],
    ): void {
        $payload = new AgentPayload(
            agentId: $agentId,
            credentialId: $credentialId,
            instruction: $this->buildInstruction($input),
            context: $context,
        );

        $this->agentService->executeAsync($payload);
    }

    /**
     * Get the underlying AgentService.
     */
    protected function getAgentService(): AgentServiceContract
    {
        return $this->agentService;
    }
}
