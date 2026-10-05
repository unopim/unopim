<?php

namespace Webkul\MagicAI\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Webkul\MagicAI\MagicAI;

/**
 * Dynamic AI agent for Magic AI content generation.
 *
 * Temperature and max tokens are resolved natively by laravel/ai via the
 * optional agent option methods; a null temperature omits the parameter,
 * which reasoning models require.
 */
class MagicContentAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $systemPrompt = '',
        protected ?float $temperature = 0.7,
        protected ?int $maxTokens = MagicAI::DEFAULT_MAX_TOKENS,
    ) {}

    public function instructions(): string
    {
        return $this->systemPrompt;
    }

    public function temperature(): ?float
    {
        return $this->temperature;
    }

    public function maxTokens(): ?int
    {
        return $this->maxTokens;
    }
}
