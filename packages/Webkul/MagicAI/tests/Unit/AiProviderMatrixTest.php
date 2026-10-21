<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Laravel\Ai\Enums\Lab;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\LaravelAiAdapter;

/**
 * Every supported platform, with the contract each one is expected to honour.
 * A new case added to the enum without a row here fails the coverage test
 * below, so provider support can never be added untested.
 *
 * @return array<string, array{0: AiProvider, 1: array<string, mixed>}>
 */
function platformMatrix(): array
{
    return [
        'openai' => [AiProvider::OpenAI, [
            'lab'       => Lab::OpenAI,
            'configKey' => 'openai',
            'url'       => 'https://api.openai.com/v1',
            'hardens'   => true,
            'images'    => true,
            'body'      => ['data' => [['id' => 'gpt-4o-mini'], ['id' => 'gpt-4o']]],
            'endpoint'  => 'https://api.openai.com/v1/models',
            'models'    => ['gpt-4o', 'gpt-4o-mini'],
            'auth'      => ['Authorization' => 'Bearer platform-key'],
        ]],
        'anthropic' => [AiProvider::Anthropic, [
            'lab'       => Lab::Anthropic,
            'configKey' => 'anthropic',
            'url'       => 'https://api.anthropic.com/v1',
            'hardens'   => true,
            'images'    => false,
            'body'      => ['data' => [['id' => 'claude-sonnet-4-5'], ['id' => 'claude-haiku-4-5']]],
            'endpoint'  => 'https://api.anthropic.com/v1/models',
            'models'    => ['claude-haiku-4-5', 'claude-sonnet-4-5'],
            'auth'      => ['x-api-key' => 'platform-key', 'anthropic-version' => '2023-06-01'],
        ]],
        'gemini' => [AiProvider::Gemini, [
            'lab'       => Lab::Gemini,
            'configKey' => 'gemini',
            'url'       => 'https://generativelanguage.googleapis.com/v1beta',
            'hardens'   => true,
            'images'    => true,
            'body'      => ['models' => [['name' => 'models/gemini-2.0-flash'], ['name' => 'models/gemini-1.5-pro']]],
            'endpoint'  => 'https://generativelanguage.googleapis.com/v1beta/models?key=platform-key',
            'models'    => ['gemini-1.5-pro', 'gemini-2.0-flash'],
            'auth'      => [],
        ]],
        'groq' => [AiProvider::Groq, [
            'lab'       => Lab::Groq,
            'configKey' => 'groq',
            'url'       => 'https://api.groq.com/openai/v1',
            'hardens'   => true,
            'images'    => false,
            'body'      => ['data' => [['id' => 'llama-3.3-70b-versatile']]],
            'endpoint'  => 'https://api.groq.com/openai/v1/models',
            'models'    => ['llama-3.3-70b-versatile'],
            'auth'      => ['Authorization' => 'Bearer platform-key'],
        ]],
        'ollama' => [AiProvider::Ollama, [
            'lab'       => Lab::Ollama,
            'configKey' => 'ollama',
            'url'       => 'http://localhost:11434',
            'hardens'   => true,
            'images'    => false,
            'body'      => ['models' => [['name' => 'llama3.2'], ['name' => 'gemma3']]],
            'endpoint'  => 'http://localhost:11434/api/tags',
            'models'    => ['gemma3', 'llama3.2'],
            'auth'      => [],
        ]],
        'xai' => [AiProvider::XAI, [
            'lab'       => Lab::xAI,
            'configKey' => 'xai',
            'url'       => 'https://api.x.ai/v1',
            'hardens'   => true,
            'images'    => true,
            'body'      => ['data' => [['id' => 'grok-4']]],
            'endpoint'  => 'https://api.x.ai/v1/models',
            'models'    => ['grok-4'],
            'auth'      => ['Authorization' => 'Bearer platform-key'],
        ]],
        'mistral' => [AiProvider::Mistral, [
            'lab'       => Lab::Mistral,
            'configKey' => 'mistral',
            'url'       => 'https://api.mistral.ai/v1',
            'hardens'   => true,
            'images'    => false,
            'body'      => ['data' => [['id' => 'mistral-large-latest']]],
            'endpoint'  => 'https://api.mistral.ai/v1/models',
            'models'    => ['mistral-large-latest'],
            'auth'      => ['Authorization' => 'Bearer platform-key'],
        ]],
        'deepseek' => [AiProvider::DeepSeek, [
            'lab'       => Lab::DeepSeek,
            'configKey' => 'deepseek',
            'url'       => 'https://api.deepseek.com',
            'hardens'   => true,
            'images'    => false,
            'body'      => ['data' => [['id' => 'deepseek-chat']]],
            'endpoint'  => 'https://api.deepseek.com/models',
            'models'    => ['deepseek-chat'],
            'auth'      => ['Authorization' => 'Bearer platform-key'],
        ]],
        'azure' => [AiProvider::Azure, [
            'lab'         => Lab::Azure,
            'configKey'   => 'azure',
            'url'         => '',
            'hardens'     => true,
            'images'      => false,
            'body'        => ['data' => [['id' => 'gpt-4o']]],
            'apiUrl'      => 'https://contoso.azure.test',
            'endpoint'    => 'https://contoso.azure.test/openai/v1/models?api-version=preview',
            'models'      => ['gpt-4o'],
            'auth'        => ['api-key' => 'platform-key'],
            'requiresUrl' => true,
        ]],
        'openrouter' => [AiProvider::OpenRouter, [
            'lab'       => Lab::OpenRouter,
            'configKey' => 'openrouter',
            'url'       => 'https://openrouter.ai/api/v1',
            'hardens'   => true,
            'images'    => false,
            'body'      => ['data' => [['id' => 'openai/gpt-4o']]],
            'endpoint'  => 'https://openrouter.ai/api/v1/models',
            'models'    => ['openai/gpt-4o'],
            'auth'      => ['Authorization' => 'Bearer platform-key'],
        ]],
        'concentrate' => [AiProvider::Concentrate, [
            'lab'       => Lab::OpenAICompatible,
            'configKey' => 'openai-compatible',
            'url'       => 'https://api.concentrate.ai/v1',
            'hardens'   => true,
            'images'    => false,
            'body'      => ['data' => [['id' => 'concentrate-chat']]],
            'endpoint'  => 'https://api.concentrate.ai/v1/models',
            'models'    => ['concentrate-chat'],
            'auth'      => ['Authorization' => 'Bearer platform-key'],
        ]],
        'custom' => [AiProvider::Custom, [
            'lab'         => Lab::OpenAICompatible,
            'configKey'   => 'openai-compatible',
            'url'         => '',
            'hardens'     => true,
            'images'      => false,
            'body'        => ['data' => [['id' => 'llama3.1-8b']]],
            'apiUrl'      => 'https://api.cerebras.test/v1',
            'endpoint'    => 'https://api.cerebras.test/v1/models',
            'models'      => ['llama3.1-8b'],
            'auth'        => ['Authorization' => 'Bearer platform-key'],
            'requiresUrl' => true,
        ]],
    ];
}

/**
 * @param  array<int, Response|Throwable>  $responses
 * @param  array<int, array<string, mixed>>  $history
 */
function matrixClient(array $responses, array &$history = []): Client
{
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    return new Client(['handler' => $stack]);
}

function platformFor(AiProvider $provider, array $expected, array $overrides = []): MagicAIPlatform
{
    return new MagicAIPlatform(array_merge([
        'label'    => $provider->label(),
        'provider' => $provider->value,
        'api_url'  => $expected['apiUrl'] ?? $expected['url'],
        'api_key'  => 'platform-key',
        'models'   => implode(',', $expected['models']),
        'status'   => true,
    ], $overrides));
}

function adapterOverrides(MagicAIPlatform $platform, string $model = 'test-model'): array
{
    $adapter = new LaravelAiAdapter(platform: $platform, model: $model, prompt: 'hi');

    return (new ReflectionMethod($adapter, 'providerOverrides'))->invoke($adapter);
}

it('covers every provider the enum offers', function () {
    expect(array_values(array_map(fn (array $row): string => $row[0]->value, platformMatrix())))
        ->toEqualCanonicalizing(array_column(AiProvider::cases(), 'value'));
});

it('maps the platform to the right laravel/ai lab, config namespace and base url', function (AiProvider $provider, array $expected) {
    expect($provider->toLab())->toBe($expected['lab'])
        ->and($provider->configKey())->toBe($expected['configKey'])
        ->and($provider->defaultUrl())->toBe($expected['url'])
        ->and($provider->supportsImages())->toBe($expected['images'])
        ->and($provider->label())->not->toBe('');
})->with(platformMatrix());

it('lists the platform models from its own discovery endpoint', function (AiProvider $provider, array $expected) {
    $history = [];
    $client = matrixClient([new Response(200, [], json_encode($expected['body']))], $history);

    $models = $provider->fetchModels('platform-key', $expected['apiUrl'] ?? null, $client);
    $request = $history[0]['request'];

    expect($models)->toBe($expected['models'])
        ->and((string) $request->getUri())->toBe($expected['endpoint']);

    foreach ($expected['auth'] as $header => $value) {
        expect($request->getHeaderLine($header))->toBe($value);
    }
})->with(platformMatrix());

it('hardens model discovery against redirects when the platform supplies its own base url', function (AiProvider $provider, array $expected) {
    $history = [];
    $client = matrixClient([new Response(200, [], json_encode($expected['body']))], $history);

    $provider->fetchModels('platform-key', $expected['apiUrl'] ?? $expected['url'] ?: null, $client);

    expect($history[0]['options']['allow_redirects'] ?? null)->toBe(false);
})->with(array_filter(platformMatrix(), fn (array $row): bool => $row[1]['hardens']));

it('sends the platform credential to the generation provider without a global fallback', function (AiProvider $provider, array $expected) {
    $overrides = adapterOverrides(platformFor($provider, $expected));

    expect($overrides['key'])->toBe('platform-key');

    if (($expected['apiUrl'] ?? $expected['url']) !== '') {
        expect($overrides['url'])->toBe($expected['apiUrl'] ?? $expected['url']);
    }
})->with(platformMatrix());

it('resolves the base url for a platform that stores none', function (AiProvider $provider, array $expected) {
    $platform = platformFor($provider, $expected, ['api_url' => null]);

    // A custom endpoint has no safe default: generating against the global
    // openai-compatible url would ship this platform's key to another host.
    if ($provider === AiProvider::Custom) {
        expect(fn (): array => adapterOverrides($platform))->toThrow(RuntimeException::class);

        return;
    }

    // Concentrate shares the openai-compatible namespace, so it pins its own
    // url rather than inheriting whatever another platform left there.
    if ($provider === AiProvider::Concentrate) {
        expect(adapterOverrides($platform)['url'])->toBe($provider->defaultUrl());

        return;
    }

    expect(adapterOverrides($platform))->not->toHaveKey('url');
})->with(platformMatrix());

it('keeps a platform extras payload from redirecting the provider endpoint', function (AiProvider $provider, array $expected) {
    $platform = platformFor($provider, $expected, [
        'extras' => ['url' => 'http://169.254.169.254', 'api_key' => 'stolen', 'organization' => 'org-1'],
    ]);

    $overrides = adapterOverrides($platform);

    expect($overrides['key'])->toBe('platform-key')
        ->and($overrides['organization'])->toBe('org-1')
        ->and($overrides['url'] ?? '')->not->toBe('http://169.254.169.254');
})->with(platformMatrix());
