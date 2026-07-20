<?php

namespace Webkul\AiAgent\Chat;

use Laravel\Ai\AnonymousAgent;

/**
 * An AnonymousAgent that advertises a hard tool-loop step cap.
 *
 * laravel/ai reads the cap from a `maxSteps()` method on the agent
 * (TextGenerationOptions::forAgent → TextGenerationLoop::resolveMaxSteps).
 * Without it the loop defaults to round(count(tools) * 1.5) — ~51 iterations
 * for the current tool set — and each iteration resends the full system
 * prompt, tool schemas, and history, so a single chat turn could burn
 * hundreds of thousands of tokens.
 */
class BoundedAgent extends AnonymousAgent
{
    /**
     * Fallback cap when a caller does not supply one, keeping the loop bounded.
     */
    public const DEFAULT_MAX_STEPS = 5;

    public function __construct(
        string $instructions,
        iterable $messages,
        iterable $tools,
        protected int $maxSteps = self::DEFAULT_MAX_STEPS,
    ) {
        parent::__construct($instructions, $messages, $tools);
    }

    public function maxSteps(): int
    {
        return $this->maxSteps;
    }
}
