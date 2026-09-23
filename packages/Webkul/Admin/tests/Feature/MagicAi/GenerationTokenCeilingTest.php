<?php

use Illuminate\Support\Collection;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\Data\Usage;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Services\LaravelAiAdapter;

function agentResponseFinishing(FinishReason $reason, string $text = 'content'): AgentResponse
{
    return (new AgentResponse('invocation', $text, new Usage, new Meta))
        ->withSteps(new Collection([
            new Step($text, [], [], $reason, new Usage, new Meta),
        ]));
}

it('reports a generation stopped by the token ceiling as truncated', function () {
    expect(LaravelAiAdapter::hitTokenCeiling(agentResponseFinishing(FinishReason::Length)))->toBeTrue();
});

it('does not report a naturally finished generation as truncated', function () {
    expect(LaravelAiAdapter::hitTokenCeiling(agentResponseFinishing(FinishReason::Stop)))->toBeFalse();
});

it('treats a response without steps as finished', function () {
    $response = new AgentResponse('invocation', 'content', new Usage, new Meta);

    expect(LaravelAiAdapter::hitTokenCeiling($response))->toBeFalse();
});

it('drops the unterminated tag left behind by a mid-markup cut', function () {
    expect(LaravelAiAdapter::dropDanglingMarkup('<p>Washing &amp; Care</p><'))
        ->toBe('<p>Washing &amp; Care</p>');
});

it('leaves complete markup untouched', function () {
    $html = '<h2>Usage</h2><ul><li>Wash cold</li></ul>';

    expect(LaravelAiAdapter::dropDanglingMarkup($html))->toBe($html);
});

it('falls back to the code default when no ceiling is configured', function () {
    expect(MagicAI::defaultMaxTokens())->toBe(MagicAI::DEFAULT_MAX_TOKENS);
});
