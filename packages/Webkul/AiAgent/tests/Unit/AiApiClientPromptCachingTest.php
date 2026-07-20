<?php

use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;

/**
 * Test double: captures the request body instead of performing a cURL call.
 */
class AiApiClientCaptureDouble extends AiApiClient
{
    /** @var array<string, mixed> */
    public array $capturedBody = [];

    /** @var array<string, mixed> */
    public array $fakeResponse = [];

    protected function execute(string $method, string $url, ?array $data = null): array
    {
        $this->capturedBody = $data ?? [];

        return $this->fakeResponse;
    }
}

function makeCachingTestClient(string $provider, string $model): AiApiClientCaptureDouble
{
    $client = new AiApiClientCaptureDouble;

    $client->configure(new CredentialConfig(
        id: 1,
        label: 'Test credential',
        provider: $provider,
        apiUrl: 'https://api.example.test',
        apiKey: 'test-key',
        model: $model,
    ));

    return $client;
}

describe('AiApiClient prompt caching (Issue #421)', function () {

    it('sends the anthropic system prompt as a block with ephemeral cache_control', function () {
        $client = makeCachingTestClient('anthropic', 'claude-3-5-sonnet');
        $client->fakeResponse = [
            'content' => [['text' => 'ok']],
            'usage'   => ['input_tokens' => 10, 'output_tokens' => 5],
        ];

        $client->chat([
            ['role' => 'system', 'content' => 'You are a PIM assistant.'],
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        expect($client->capturedBody['system'])->toBe([
            [
                'type'          => 'text',
                'text'          => 'You are a PIM assistant.',
                'cache_control' => ['type' => 'ephemeral'],
            ],
        ])->and(array_column($client->capturedBody['messages'], 'role'))->toBe(['user']);
    });

    it('omits the anthropic system field when no system message exists', function () {
        $client = makeCachingTestClient('anthropic', 'claude-3-5-sonnet');
        $client->fakeResponse = ['content' => [['text' => 'ok']], 'usage' => []];

        $client->chat([['role' => 'user', 'content' => 'Hello']]);

        expect($client->capturedBody)->not->toHaveKey('system');
    });

    it('parses anthropic cached tokens from cache_read and cache_creation usage', function () {
        $client = makeCachingTestClient('anthropic', 'claude-3-5-sonnet');
        $client->fakeResponse = [
            'content' => [['text' => 'ok']],
            'usage'   => [
                'input_tokens'                => 20,
                'output_tokens'               => 10,
                'cache_read_input_tokens'     => 100,
                'cache_creation_input_tokens' => 50,
            ],
        ];

        $result = $client->chat([['role' => 'user', 'content' => 'Hello']]);

        // Anthropic's input_tokens excludes cached tokens; tokensUsed must
        // include them so budgets reflect real usage: 20 + 10 + 100 + 50.
        expect($result['tokensUsed'])->toBe(180)
            ->and($result['cachedTokens'])->toBe(150);
    });

    it('parses openai cached tokens from prompt_tokens_details', function () {
        $client = makeCachingTestClient('openai', 'gpt-4o');
        $client->fakeResponse = [
            'choices' => [['message' => ['content' => 'ok']]],
            'usage'   => [
                'total_tokens'          => 42,
                'prompt_tokens_details' => ['cached_tokens' => 30],
            ],
        ];

        $result = $client->chat([['role' => 'user', 'content' => 'Hello']]);

        expect($result['tokensUsed'])->toBe(42)
            ->and($result['cachedTokens'])->toBe(30);
    });

    it('defaults cached tokens to zero when providers omit cache usage', function () {
        $client = makeCachingTestClient('openai', 'gpt-4o');
        $client->fakeResponse = [
            'choices' => [['message' => ['content' => 'ok']]],
            'usage'   => ['total_tokens' => 42],
        ];

        $result = $client->chat([['role' => 'user', 'content' => 'Hi']]);

        expect($result['cachedTokens'])->toBe(0);
    });
});

describe('AiApiClient pre-flight token estimation (Issue #423)', function () {

    it('logs the token estimate before every request', function () {
        Log::spy();

        $client = makeCachingTestClient('openai', 'gpt-4o');
        $client->fakeResponse = ['choices' => [['message' => ['content' => 'ok']]], 'usage' => []];

        $client->chat([['role' => 'user', 'content' => 'Hello']]);

        Log::shouldHaveReceived('debug')
            ->withArgs(fn ($message) => $message === 'AI Agent pre-flight token estimate')
            ->once();
    });

    it('trims the oldest history when the estimate exceeds the context window', function () {
        config(['ai-agent.token_estimation.default_context_window' => 1200]);

        $client = makeCachingTestClient('openai', 'custom-small-model');
        $client->fakeResponse = ['choices' => [['message' => ['content' => 'ok']]], 'usage' => []];

        $client->chat(
            messages: [
                ['role' => 'system', 'content' => 'You are helpful.'],
                ['role' => 'user', 'content' => str_repeat('a', 6000)],
                ['role' => 'assistant', 'content' => str_repeat('b', 6000)],
                ['role' => 'user', 'content' => 'Final question'],
            ],
            maxTokens: 100,
        );

        $sent = $client->capturedBody['messages'];

        expect($sent)->toHaveCount(2)
            ->and($sent[0]['role'])->toBe('system')
            ->and($sent[1]['content'])->toBe('Final question');
    });

    it('trims the largest system context block before failing', function () {
        config(['ai-agent.token_estimation.default_context_window' => 1200]);

        $client = makeCachingTestClient('openai', 'custom-small-model');
        $client->fakeResponse = ['choices' => [['message' => ['content' => 'ok']]], 'usage' => []];

        $client->chat(
            messages: [
                ['role' => 'system', 'content' => 'You are helpful.'],
                ['role' => 'system', 'content' => 'Context data: '.str_repeat('c', 8000)],
                ['role' => 'user', 'content' => 'Final question'],
            ],
            maxTokens: 100,
        );

        $sent = $client->capturedBody['messages'];

        expect($sent)->toHaveCount(2)
            ->and($sent[0]['content'])->toBe('You are helpful.')
            ->and($sent[1]['content'])->toBe('Final question');
    });

    it('throws a clear exception when the request cannot be trimmed to fit', function () {
        config(['ai-agent.token_estimation.default_context_window' => 300]);

        $client = makeCachingTestClient('openai', 'custom-small-model');

        $client->chat(
            messages: [['role' => 'user', 'content' => str_repeat('a', 8000)]],
            maxTokens: 100,
        );
    })->throws(RuntimeException::class, 'Your request is too large');

    it('resolves the context window by longest model prefix with config overrides', function () {
        config(['ai-agent.token_estimation.context_windows' => ['my-model' => 2000]]);
        config(['ai-agent.token_estimation.default_context_window' => 1200]);

        $client = makeCachingTestClient('openai', 'my-model-v2');
        $client->fakeResponse = ['choices' => [['message' => ['content' => 'ok']]], 'usage' => []];

        // ~1600 estimated tokens: over the 1200 default but under the 2000 override,
        // so nothing may be trimmed.
        $client->chat(
            messages: [
                ['role' => 'system', 'content' => 'You are helpful.'],
                ['role' => 'user', 'content' => str_repeat('a', 6200)],
            ],
            maxTokens: 100,
        );

        expect($client->capturedBody['messages'])->toHaveCount(2);
    });
});
