<?php

use Illuminate\Support\Facades\Http;
use Laravel\Ai\Enums\Lab;
use Webkul\MagicAI\Enums\AiProvider;

it('exposes a Custom case routed through the openai-compatible config namespace', function () {
    expect(AiProvider::Custom->value)->toBe('custom');
    expect(AiProvider::Custom->label())->toBe('Custom (OpenAI-compatible)');
    expect(AiProvider::Custom->defaultUrl())->toBe('');
    expect(AiProvider::Custom->configKey())->toBe('openai-compatible');
    expect(AiProvider::Custom->supportsImages())->toBeFalse();
});

it('maps Custom to Lab::OpenAICompatible for laravel/ai routing', function () {
    // Custom platforms (Cerebras, Together, Fireworks, Perplexity, DeepInfra,
    // etc.) implement OpenAI's /chat/completions endpoint, which laravel/ai's
    // dedicated OpenAI-compatible driver speaks.
    expect(AiProvider::Custom->toLab())->toBe(Lab::OpenAICompatible);
});

it('lists the Custom provider in the dropdown options payload', function () {
    $options = AiProvider::options();

    expect($options)->toContain([
        'title' => 'Custom (OpenAI-compatible)',
        'value' => 'custom',
    ]);
});

it('returns an empty model list for Custom when no api_url is supplied', function () {
    expect(AiProvider::Custom->fetchModels('sk-test-key', null))->toBe([]);
    expect(AiProvider::Custom->fetchModels('sk-test-key', ''))->toBe([]);
});

it('surfaces the upstream Cerebras-style 402 body when Test Connection fails on a Custom platform', function () {
    $this->loginAsAdmin();

    // Cerebras-style 402 with the error message at the top level of the JSON
    // body (NOT nested under "error.message") — the exact shape that
    // triggered the original "Unknown error" bug.
    Http::fake([
        '*/chat/completions' => Http::response([
            'message' => 'Payment required to access this resource. Visit your billing tab.',
            'type'    => 'payment_required_error',
            'param'   => 'quota',
            'code'    => 'payment_required',
        ], 402),
    ]);

    $response = $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => AiProvider::Custom->value,
        'api_key'  => 'csk-test-key-1234567890',
        'api_url'  => 'https://api.cerebras.ai/v1',
        'models'   => 'llama3.1-8b',
    ]);

    expect($response->status())->toBeIn([400, 402]);
    $body = $response->json();

    expect($body['success'])->toBeFalse();
    expect(
        str_contains($body['message'], 'insufficient credits')
        || str_contains($body['message'], 'Payment required to access this resource')
    )->toBeTrue();
    expect($body['message'])->not->toContain('Unknown error');
    expect($body['message'])->not->toContain('OpenAI-compatible Error');
    expect($body['message'])->not->toContain('Groq Error');
});

it('rewrites a leaked gateway error prefix to Custom Provider Error for Custom platforms', function () {
    $this->loginAsAdmin();

    // A non-JSON upstream body leaves the gateway's own "OpenAI-compatible
    // Error" prefix intact; the controller must rewrite it so the message
    // reflects the user's selected provider.
    Http::fake([
        '*/chat/completions' => Http::response('non-json server crash output', 500),
    ]);

    $response = $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => AiProvider::Custom->value,
        'api_key'  => 'csk-test-key-1234567890',
        'api_url'  => 'https://api.cerebras.ai/v1',
        'models'   => 'llama3.1-8b',
    ]);

    $body = $response->json();
    expect($body['message'])->not->toContain('OpenAI-compatible Error');
    expect($body['message'])->not->toContain('Groq Error');
});

it('does not leak Custom platform credentials into config after the test call', function () {
    $this->loginAsAdmin();

    Http::fake([
        '*/chat/completions' => Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => 'OK']]]], 200),
    ]);

    $originalKey = config('ai.providers.openai-compatible.key');
    $originalUrl = config('ai.providers.openai-compatible.url');

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => AiProvider::Custom->value,
        'api_key'  => 'csk-secret-key-1234567890',
        'api_url'  => 'https://api.cerebras.ai/v1',
        'models'   => 'llama3.1-8b',
    ]);

    expect(config('ai.providers.openai-compatible.key'))->toBe($originalKey);
    expect(config('ai.providers.openai-compatible.url'))->toBe($originalUrl);
});
