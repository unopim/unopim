<?php

use Illuminate\Support\Facades\Http;
use Prism\Prism\Enums\Provider as PrismProvider;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Services\LaravelAiAdapter;

it('exposes a Custom case routed through the Groq Prism provider', function () {
    expect(AiProvider::Custom->value)->toBe('custom');
    expect(AiProvider::Custom->label())->toBe('Custom (OpenAI-compatible)');
    expect(AiProvider::Custom->defaultUrl())->toBe('');
    // Custom shares the groq config namespace so api_url overrides land
    // where Prism's Groq provider (which posts to /chat/completions) reads.
    expect(AiProvider::Custom->configKey())->toBe('groq');
    expect(AiProvider::Custom->supportsImages())->toBeFalse();
    expect(AiProvider::Custom->toPrismProvider())->toBe(PrismProvider::Groq);
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

it('maps Custom to PrismProvider::Groq inside LaravelAiAdapter', function () {
    $reflection = new ReflectionClass(LaravelAiAdapter::class);
    $method = $reflection->getMethod('toPrismProvider');
    $method->setAccessible(true);

    $instance = $reflection->newInstanceWithoutConstructor();

    $aiProviderProp = $reflection->getProperty('aiProvider');
    $aiProviderProp->setAccessible(true);
    $aiProviderProp->setValue($instance, AiProvider::Custom);

    expect($method->invoke($instance))->toBe(PrismProvider::Groq);
});

it('surfaces the upstream Cerebras-style 402 body when Test Connection fails on a Custom platform', function () {
    $this->loginAsAdmin();

    // Intercept the chat-completions call Prism makes via Laravel's HTTP
    // client and return a Cerebras-style 402 with the error message at the
    // top level of the JSON body (NOT nested under "error.message"). This
    // is the exact shape that triggered the original "Unknown error" bug.
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

    $response->assertStatus(400);
    $body = $response->json();

    expect($body['success'])->toBeFalse();
    // The clean upstream message must reach the user — no "Unknown error",
    // no leaked "Groq Error" prefix, and the actual Cerebras text included.
    expect($body['message'])->toContain('Payment required to access this resource');
    expect($body['message'])->not->toContain('Unknown error');
    expect($body['message'])->not->toContain('Groq Error');
});

it('rewrites a leaked Groq Error prefix to Custom Provider Error for Custom platforms', function () {
    $this->loginAsAdmin();

    // Force the message Prism produces to contain the literal "Groq Error"
    // prefix (the resolver leaves it intact when the upstream body has no
    // recognisable JSON to extract).
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
    expect($body['message'])->not->toContain('Groq Error');
});
