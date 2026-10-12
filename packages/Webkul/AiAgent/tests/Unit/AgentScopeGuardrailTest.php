<?php

use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * The chat agent must stay inside the PIM/e-commerce domain. Off-topic
 * questions (general knowledge, news, politics) must be declined and
 * redirected — the system prompt is the enforcement point.
 */
function buildGuardrailSystemPrompt(): string
{
    $runner = new AgentRunner(new ToolRegistry);

    $context = new ChatContext(
        message: 'Who is the prime minister of India?',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform([
            'provider' => 'openai',
            'models'   => 'gpt-4o',
        ]),
        model: 'gpt-4o',
    );

    $method = new ReflectionMethod($runner, 'buildSystemPrompt');

    return $method->invoke($runner, $context);
}

it('instructs the agent to stay within the PIM and e-commerce domain', function () {
    $prompt = buildGuardrailSystemPrompt();

    expect($prompt)->toContain('SCOPE');
    expect($prompt)->toContain('decline');
});

it('instructs the agent to redirect off-topic questions back to PIM capabilities', function () {
    $prompt = buildGuardrailSystemPrompt();

    expect($prompt)->toContain('unrelated');
    expect($prompt)->toContain('redirect');
});
