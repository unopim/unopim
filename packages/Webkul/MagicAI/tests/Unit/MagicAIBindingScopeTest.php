<?php

use Illuminate\Support\Facades\Facade;
use Webkul\MagicAI\Facades\MagicAI as MagicAIFacade;
use Webkul\MagicAI\MagicAI;

function magicAiState(MagicAI $magicAi, string $property): mixed
{
    return (new ReflectionProperty($magicAi, $property))->getValue($magicAi);
}

/**
 * Mirror what a queue worker and Octane do between two jobs or requests.
 */
function endRequestScope(): void
{
    app()->forgetScopedInstances();

    Facade::clearResolvedInstances();
}

it('gives the next request or job a clean magic ai builder', function () {
    MagicAIFacade::setModel('model-from-request-one')
        ->setSystemPrompt('system prompt from request one')
        ->setTemperature(0.1)
        ->setMaxTokens(99)
        ->setStream(true)
        ->setPrompt('prompt from request one', 'text');

    endRequestScope();

    $next = MagicAIFacade::getFacadeRoot();

    expect(magicAiState($next, 'model'))->toBeNull()
        ->and(magicAiState($next, 'systemPrompt'))->toBe('')
        ->and(magicAiState($next, 'prompt'))->toBe('')
        ->and(magicAiState($next, 'temperature'))->toBe(0.7)
        ->and(magicAiState($next, 'maxTokens'))->toBe(MagicAI::DEFAULT_MAX_TOKENS)
        ->and(magicAiState($next, 'stream'))->toBeFalse()
        ->and(magicAiState($next, 'platformRecord'))->toBeNull();
});

it('does not hand the same instance to the next request or job', function () {
    $first = magic_ai();

    endRequestScope();

    expect(magic_ai())->not->toBe($first);
});

it('shares one instance between the facade and the helper within a request', function () {
    expect(MagicAIFacade::getFacadeRoot())->toBe(magic_ai())
        ->and(magic_ai())->toBeInstanceOf(MagicAI::class);
});
