<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Webkul\MagicAI\Enums\AiProvider;

function magicAiMockClient(array $responses, ?array &$reached = null): Client
{
    $stack = HandlerStack::create(new MockHandler($responses));

    $stack->push(function (callable $handler) use (&$reached) {
        return function ($request, array $options) use ($handler, &$reached) {
            $reached[] = (string) $request->getUri();

            return $handler($request, $options);
        };
    });

    return new Client(['timeout' => 15, 'handler' => $stack]);
}

it('discovers models when the custom base url omits the /v1 path segment', function () {
    $reached = [];

    $client = magicAiMockClient([
        new Response(403, ['Content-Type' => 'text/html'], '<!DOCTYPE html><html>Forbidden</html>'),
        new Response(200, [], json_encode(['data' => [['id' => 'gpt-4o-mini']]])),
    ], $reached);

    $discovery = AiProvider::Custom->discoverModels('sk-test-key', 'https://1.1.1.1', $client);

    expect($discovery['models'])->toBe(['gpt-4o-mini']);
    expect($discovery['api_url'])->toBe('https://1.1.1.1/v1');
    expect($reached)->toBe(['https://1.1.1.1/models', 'https://1.1.1.1/v1/models']);
});

it('keeps the submitted base url when it already answers on /models', function () {
    $client = magicAiMockClient([
        new Response(200, [], json_encode(['data' => [['id' => 'llama3.1-8b']]])),
    ]);

    $discovery = AiProvider::Custom->discoverModels('sk-test-key', 'https://1.1.1.1/v1', $client);

    expect($discovery['models'])->toBe(['llama3.1-8b']);
    expect($discovery['api_url'])->toBe('https://1.1.1.1/v1');
});

it('reduces an HTML error body to a short status message', function () {
    $client = magicAiMockClient([
        new Response(403, ['Content-Type' => 'text/html'], '<!DOCTYPE html><html class="no-js ie6 oldie" lang="en-US">'.str_repeat('x', 5000).'</html>'),
        new Response(403, ['Content-Type' => 'text/html'], '<!DOCTYPE html><html>Forbidden</html>'),
    ]);

    expect(fn () => AiProvider::Custom->discoverModels('sk-test-key', 'https://1.1.1.1', $client))
        ->toThrow(function (RuntimeException $e) {
            expect($e->getMessage())->toContain('403');
            expect($e->getMessage())->toContain('1.1.1.1');
            expect($e->getMessage())->not->toContain('<!DOCTYPE');
            expect(strlen($e->getMessage()))->toBeLessThan(300);
        });
});

it('surfaces a json error message from the upstream endpoint', function () {
    $client = magicAiMockClient([
        new Response(401, [], json_encode(['error' => ['message' => 'Invalid API key provided.']])),
        new Response(401, [], json_encode(['error' => ['message' => 'Invalid API key provided.']])),
    ]);

    expect(fn () => AiProvider::Custom->discoverModels('sk-bad-key', 'https://1.1.1.1', $client))
        ->toThrow(RuntimeException::class, 'Invalid API key provided.');
});
