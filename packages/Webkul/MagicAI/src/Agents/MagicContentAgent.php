<?php

namespace Webkul\MagicAI\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * A dynamic AI agent for Magic AI content generation.
 *
 * Uses the Laravel AI SDK's Agent interface properly.
 * Temperature and MaxTokens are passed via HasProviderOptions
 * since they need to be dynamic (set per-request from system prompts).
 */
class MagicContentAgent implements Agent, HasProviderOptions
{
    use Promptable;

    public function __construct(
        protected string $systemPrompt = '',
        protected float $temperature = 0.7,
        protected int $maxTokens = 1054,
    ) {}

    /**
     * The system instructions for the agent.
     */
    public function instructions(): string
    {
        return $this->systemPrompt;
    }

    /**
     * Provider-specific options passed to the underlying SDK.
     * This is the proper way to pass dynamic temperature/maxTokens
     * in the Laravel AI SDK.
     */
    public function providerOptions(Lab|string $provider): array
    {
        return [
            'temperature' => $this->temperature,
            'max_tokens'  => $this->maxTokens,
        ];
    }
}
