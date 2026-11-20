<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\LaravelAiAdapter;
use Webkul\User\Models\Admin;

beforeEach(function (): void {
    $this->platform = MagicAIPlatform::create([
        'label'      => 'Pinned Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://1.1.1.1/v1',
        'api_key'    => 'sk-test-PinnedPlatformKey0123456789',
        'models'     => 'gpt-4o-mini,dall-e-3,text-embedding-3-small',
        'status'     => true,
        'is_default' => false,
    ]);

    $this->sentOptions = [];

    Http::fake(function (Request $request, array $options) {
        $this->sentOptions[] = $options;

        return match (true) {
            str_contains($request->url(), '/images/')     => Http::response(['data' => [['b64_json' => base64_encode('fake-image')]]]),
            str_contains($request->url(), '/embeddings')  => Http::response(['data' => [['embedding' => [0.1, 0.2], 'index' => 0]], 'usage' => ['prompt_tokens' => 1, 'total_tokens' => 1]]),
            default                                       => Http::response([
                'id'      => 'resp_1',
                'object'  => 'chat.completion',
                'model'   => 'gpt-4o-mini',
                'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'OK'], 'finish_reason' => 'stop']],
                'usage'   => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
            ]),
        };
    });
});

/**
 * Assert every provider request was pinned to the platform IP with redirects off.
 *
 * @param  array<int, array<string, mixed>>  $sentOptions
 */
function expectPinnedWithoutRedirects(array $sentOptions): void
{
    expect($sentOptions)->not->toBeEmpty();

    foreach ($sentOptions as $options) {
        expect($options['allow_redirects'] ?? null)->toBeFalse()
            ->and($options['curl'][CURLOPT_RESOLVE] ?? null)->toBe(['1.1.1.1:443:1.1.1.1']);
    }
}

it('pins the connection test to the platform address with redirects off', function (): void {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => 'openai',
        'api_key'  => 'sk-test-TypedKeyForTheConnectionTest01',
        'api_url'  => 'https://1.1.1.1/v1',
        'models'   => 'gpt-4o-mini',
    ]);

    expectPinnedWithoutRedirects($this->sentOptions);
});

it('pins content generation to the platform address with redirects off', function (): void {
    rescue(fn () => (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-4o-mini',
        prompt: 'Write a product title',
    ))->ask(), report: false);

    expectPinnedWithoutRedirects($this->sentOptions);
});

it('pins image generation to the platform address with redirects off', function (): void {
    rescue(fn () => (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-3',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']), report: false);

    expectPinnedWithoutRedirects($this->sentOptions);
});

it('pins an agent chat turn to the platform address with redirects off', function (): void {
    $context = new ChatContext(
        message: 'How many products are enabled?',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: $this->platform,
        model: 'gpt-4o-mini',
        user: Admin::factory()->create(),
    );

    rescue(fn () => resolve(AgentRunner::class)->run($context), report: false);

    expectPinnedWithoutRedirects($this->sentOptions);
});
