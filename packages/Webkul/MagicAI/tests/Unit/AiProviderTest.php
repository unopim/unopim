<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Laravel\Ai\Enums\Lab;
use Webkul\MagicAI\Enums\AiProvider;

/**
 * @param  array<int, Response|Throwable>  $responses
 * @param  array<int, array<string, mixed>>  $history
 */
function fakeProviderClient(array $responses, array &$history = []): Client
{
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    return new Client(['handler' => $stack]);
}

it('maps every provider to a laravel/ai lab', function (AiProvider $provider) {
    expect($provider->toLab())->toBeInstanceOf(Lab::class);
})->with(AiProvider::cases());

it('routes OpenAI-compatible providers through the shared config namespace', function () {
    expect(AiProvider::Custom->configKey())->toBe('openai-compatible')
        ->and(AiProvider::Concentrate->configKey())->toBe('openai-compatible')
        ->and(AiProvider::Custom->toLab())->toBe(Lab::OpenAICompatible);
});

it('keeps a dedicated config namespace for first-party providers', function () {
    expect(AiProvider::OpenAI->configKey())->toBe('openai')
        ->and(AiProvider::Anthropic->configKey())->toBe('anthropic')
        ->and(AiProvider::OpenRouter->configKey())->toBe('openrouter');
});

it('only advertises image support for the providers that have an image endpoint', function () {
    $supported = array_values(array_filter(AiProvider::cases(), fn (AiProvider $p): bool => $p->supportsImages()));

    expect($supported)->toBe([AiProvider::OpenAI, AiProvider::Gemini, AiProvider::XAI]);
});

it('leaves the base url blank for providers that have no fixed endpoint', function () {
    expect(AiProvider::Azure->defaultUrl())->toBe('')
        ->and(AiProvider::Custom->defaultUrl())->toBe('');
});

it('exposes every case as a labelled select option', function () {
    $options = AiProvider::options();

    expect($options)->toHaveCount(count(AiProvider::cases()))
        ->and($options[0])->toHaveKeys(['title', 'value']);
});

it('fetches and sorts OpenAI models from the platform base url', function () {
    $history = [];
    $client = fakeProviderClient([
        new Response(200, [], json_encode(['data' => [['id' => 'gpt-4o'], ['id' => 'dall-e-3']]])),
    ], $history);

    $models = AiProvider::OpenAI->fetchModels('sk-test', 'https://proxy.test/v1', $client);

    expect($models)->toBe(['dall-e-3', 'gpt-4o'])
        ->and((string) $history[0]['request']->getUri())->toBe('https://proxy.test/v1/models')
        ->and($history[0]['request']->getHeaderLine('Authorization'))->toBe('Bearer sk-test');
});

it('falls back to the provider default url when the platform has none', function () {
    $history = [];
    $client = fakeProviderClient([new Response(200, [], json_encode(['data' => []]))], $history);

    AiProvider::OpenAI->fetchModels('sk-test', null, $client);

    expect((string) $history[0]['request']->getUri())->toBe('https://api.openai.com/v1/models');
});

it('strips the models/ prefix from Gemini model names', function () {
    $client = fakeProviderClient([
        new Response(200, [], json_encode(['models' => [['name' => 'models/gemini-2.0-flash'], ['name' => 'models/gemini-1.5-pro']]])),
    ]);

    expect(AiProvider::Gemini->fetchModels('key', null, $client))
        ->toBe(['gemini-1.5-pro', 'gemini-2.0-flash']);
});

it('sends the Anthropic version header when listing models', function () {
    $history = [];
    $client = fakeProviderClient([new Response(200, [], json_encode(['data' => [['id' => 'claude-sonnet-4-5']]]))], $history);

    expect(AiProvider::Anthropic->fetchModels('sk-ant', null, $client))->toBe(['claude-sonnet-4-5'])
        ->and($history[0]['request']->getHeaderLine('anthropic-version'))->toBe('2023-06-01')
        ->and($history[0]['request']->getHeaderLine('x-api-key'))->toBe('sk-ant');
});

it('lists Ollama models from its tags endpoint', function () {
    $history = [];
    $client = fakeProviderClient([
        new Response(200, [], json_encode(['models' => [['name' => 'llama3.2'], ['name' => 'gemma3']]])),
    ], $history);

    expect(AiProvider::Ollama->fetchModels(null, 'http://localhost:11434', $client))
        ->toBe(['gemma3', 'llama3.2'])
        ->and((string) $history[0]['request']->getUri())->toBe('http://localhost:11434/api/tags');
});

it('strips the tilde marker some OpenAI-compatible gateways prepend to model ids', function () {
    $client = fakeProviderClient([
        new Response(200, [], json_encode(['data' => [['id' => '~llama-3.3-70b']]])),
    ]);

    expect(AiProvider::Groq->fetchModels('gsk-test', null, $client))->toBe(['llama-3.3-70b']);
});

it('discovers a custom endpoint by retrying with the version segment appended', function () {
    $history = [];
    $client = fakeProviderClient([
        new Response(404, [], '{"error":{"message":"not found"}}'),
        new Response(200, [], json_encode(['data' => [['id' => 'llama3.1-8b']]])),
    ], $history);

    $discovery = AiProvider::Custom->discoverModels('key', 'https://api.cerebras.ai', $client);

    expect($discovery)->toBe(['models' => ['llama3.1-8b'], 'released' => ['llama3.1-8b' => null], 'api_url' => 'https://api.cerebras.ai/v1'])
        ->and($history)->toHaveCount(2);
});

it('does not retry a custom endpoint that already carries a version segment', function () {
    $history = [];
    $client = fakeProviderClient([new Response(404, [], '{}')], $history);

    expect(fn () => AiProvider::Custom->discoverModels('key', 'https://api.cerebras.ai/v1', $client))
        ->toThrow(RuntimeException::class);

    expect($history)->toHaveCount(1);
});

it('returns nothing for a custom provider with no base url', function () {
    expect(AiProvider::Custom->discoverModels('key', null))->toBe(['models' => [], 'released' => [], 'api_url' => '']);
});

it('summarises an upstream failure instead of surfacing the raw response body', function () {
    $client = fakeProviderClient([
        new Response(401, [], '<html><body>Unauthorized</body></html>'),
    ]);

    expect(fn () => AiProvider::OpenAI->fetchModels('bad-key', null, $client))
        ->toThrow(RuntimeException::class);

    try {
        AiProvider::OpenAI->fetchModels('bad-key', null, $client);
    } catch (RuntimeException $e) {
        expect($e->getMessage())->not->toContain('<html>');
    }
});

it('keeps the release date each provider reports alongside the model id', function () {
    $client = fakeProviderClient([
        new Response(200, [], json_encode(['data' => [['id' => 'gpt-5-mini', 'created' => 1754425928], ['id' => 'gpt-3.5-turbo', 'created' => 1677610602]]])),
        new Response(200, [], json_encode(['data' => [['id' => 'claude-sonnet-4-5', 'created_at' => '2025-09-29T00:00:00Z']]])),
    ]);

    expect(AiProvider::OpenAI->fetchModelCatalog('sk-test', null, $client))
        ->toBe(['gpt-3.5-turbo' => 1677610602, 'gpt-5-mini' => 1754425928])
        ->and(AiProvider::Anthropic->fetchModelCatalog('key', null, $client))
        ->toBe(['claude-sonnet-4-5' => strtotime('2025-09-29T00:00:00Z')]);
});

it('lists only the Gemini models that can generate content or images', function () {
    $client = fakeProviderClient([
        new Response(200, [], json_encode(['models' => [
            ['name' => 'models/gemini-3.1-flash-lite', 'supportedGenerationMethods' => ['generateContent', 'countTokens']],
            ['name' => 'models/imagen-4.0-generate-001', 'supportedGenerationMethods' => ['predict']],
            ['name' => 'models/gemini-embedding-001', 'supportedGenerationMethods' => ['embedContent']],
            ['name' => 'models/aqa', 'supportedGenerationMethods' => ['generateAnswer']],
        ]])),
    ]);

    expect(AiProvider::Gemini->fetchModels('key', null, $client))
        ->toBe(['gemini-3.1-flash-lite', 'imagen-4.0-generate-001']);
});
