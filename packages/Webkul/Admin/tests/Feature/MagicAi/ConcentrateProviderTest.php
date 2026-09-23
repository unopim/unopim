<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Laravel\Ai\Enums\Lab;
use Webkul\MagicAI\Enums\AiProvider;

it('exposes Concentrate as a first-class provider on the openai-compatible driver', function () {
    expect(AiProvider::Concentrate->value)->toBe('concentrate');
    expect(AiProvider::Concentrate->label())->toBe('Concentrate AI');
    expect(AiProvider::Concentrate->defaultUrl())->toBe('https://api.concentrate.ai/v1');
    expect(AiProvider::Concentrate->configKey())->toBe('openai-compatible');
    expect(AiProvider::Concentrate->toLab())->toBe(Lab::OpenAICompatible);
});

it('lists Concentrate in the provider dropdown options', function () {
    expect(AiProvider::options())->toContain([
        'title' => 'Concentrate AI',
        'value' => 'concentrate',
    ]);
});

it('fetches Concentrate models from its default endpoint when no api url is supplied', function () {
    $reached = [];

    $stack = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(['data' => [['id' => 'gpt-5.6-terra']]])),
    ]));

    $stack->push(function (callable $handler) use (&$reached) {
        return function ($request, array $options) use ($handler, &$reached) {
            $reached[] = (string) $request->getUri();

            return $handler($request, $options);
        };
    });

    $models = AiProvider::Concentrate->fetchModels('sk-test-key', null, new Client(['handler' => $stack]));

    expect($models)->toBe(['gpt-5.6-terra']);
    expect($reached)->toBe(['https://api.concentrate.ai/v1/models']);
});

it('discovers models against the platform base url for every hosted provider', function (AiProvider $provider, string $apiUrl, string $expected) {
    $reached = [];

    $stack = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(['data' => [['id' => 'model-a']], 'models' => [['name' => 'models/model-a']]])),
    ]));

    $stack->push(function (callable $handler) use (&$reached) {
        return function ($request, array $options) use ($handler, &$reached) {
            $reached[] = (string) $request->getUri();

            return $handler($request, $options);
        };
    });

    $provider->fetchModels('sk-test-key', $apiUrl, new Client(['handler' => $stack]));

    expect($reached[0])->toStartWith($expected);
})->with([
    'openai proxy'     => [AiProvider::OpenAI, 'https://1.1.1.1/v1', 'https://1.1.1.1/v1/models'],
    'anthropic proxy'  => [AiProvider::Anthropic, 'https://1.1.1.1/v1', 'https://1.1.1.1/v1/models'],
    'gemini proxy'     => [AiProvider::Gemini, 'https://1.1.1.1/v1beta', 'https://1.1.1.1/v1beta/models'],
    'groq proxy'       => [AiProvider::Groq, 'https://1.1.1.1/openai/v1', 'https://1.1.1.1/openai/v1/models'],
    'xai proxy'        => [AiProvider::XAI, 'https://1.1.1.1/v1', 'https://1.1.1.1/v1/models'],
    'mistral proxy'    => [AiProvider::Mistral, 'https://1.1.1.1/v1', 'https://1.1.1.1/v1/models'],
    'deepseek proxy'   => [AiProvider::DeepSeek, 'https://1.1.1.1', 'https://1.1.1.1/models'],
    'openrouter proxy' => [AiProvider::OpenRouter, 'https://1.1.1.1/api/v1', 'https://1.1.1.1/api/v1/models'],
]);

it('falls back to the provider default endpoint when no base url is configured', function () {
    $reached = [];

    $stack = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(['data' => [['id' => 'model-a']]])),
    ]));

    $stack->push(function (callable $handler) use (&$reached) {
        return function ($request, array $options) use ($handler, &$reached) {
            $reached[] = (string) $request->getUri();

            return $handler($request, $options);
        };
    });

    AiProvider::Groq->fetchModels('gsk-test-key', null, new Client(['handler' => $stack]));

    expect($reached)->toBe(['https://api.groq.com/openai/v1/models']);
});
