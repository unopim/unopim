<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Webkul\MagicAI\Enums\AiProvider;

function azureMockClient(array $responses, ?array &$reached = null): Client
{
    $stack = HandlerStack::create(new MockHandler($responses));

    $stack->push(function (callable $handler) use (&$reached) {
        return function ($request, array $options) use ($handler, &$reached) {
            $reached[] = (string) $request->getUri();

            return $handler($request, $options);
        };
    });

    return new Client(['handler' => $stack]);
}

it('lists Azure models from the v1 path the generation gateway uses', function () {
    $reached = [];

    $client = azureMockClient([
        new Response(200, [], json_encode(['data' => [['id' => 'gpt-4o']]])),
    ], $reached);

    $models = AiProvider::Azure->fetchModels('azure-key', 'https://1.1.1.1', $client);

    expect($models)->toBe(['gpt-4o']);
    expect($reached)->toBe(['https://1.1.1.1/openai/v1/models?api-version=preview']);
});

it('falls back to the dated Azure models path when the v1 path is unavailable', function () {
    $reached = [];

    $client = azureMockClient([
        new Response(404, [], json_encode(['error' => ['message' => 'Resource not found']])),
        new Response(200, [], json_encode(['data' => [['id' => 'gpt-35-turbo']]])),
    ], $reached);

    expect(AiProvider::Azure->fetchModels('azure-key', 'https://1.1.1.1', $client))->toBe(['gpt-35-turbo']);
    expect($reached[1])->toBe('https://1.1.1.1/openai/models?api-version=2024-10-21');
});

it('surfaces an Azure discovery failure instead of reporting an empty catalogue', function () {
    $client = azureMockClient([
        new Response(401, [], json_encode(['error' => ['message' => 'Access denied due to invalid subscription key.']])),
        new Response(401, [], json_encode(['error' => ['message' => 'Access denied due to invalid subscription key.']])),
    ]);

    expect(fn () => AiProvider::Azure->fetchModels('bad-key', 'https://1.1.1.1', $client))
        ->toThrow(RuntimeException::class, 'Access denied due to invalid subscription key.');
});

it('returns nothing for Azure when no endpoint is configured', function () {
    expect(AiProvider::Azure->fetchModels('azure-key', null))->toBe([]);
});
