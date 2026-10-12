<?php

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Gateways\OpenAiImageGateway;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\LaravelAiAdapter;

beforeEach(function () {
    $this->platform = MagicAIPlatform::create([
        'label'    => 'Test OpenAI',
        'provider' => 'openai',
        'api_key'  => 'sk-test',
        'models'   => json_encode(['dall-e-2', 'dall-e-3', 'gpt-image-1', 'chatgpt-image-latest']),
        'status'   => true,
    ]);

    Http::fake([
        '*' => Http::response([
            'data' => [[
                'b64_json' => base64_encode('fake-image'),
            ]],
        ]),
    ]);
});

it('does not send quality parameter to dall-e-2 when not explicitly set', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-2',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $request->url() === 'https://api.openai.com/v1/images/generations'
            && $body['model'] === 'dall-e-2'
            && ! array_key_exists('quality', $body);
    });
});

it('does not send moderation parameter to dall-e-3', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-3',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024', 'quality' => 'standard']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'dall-e-3'
            && ! array_key_exists('moderation', $body);
    });
});

it('sends quality parameter when explicitly provided', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-3',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024', 'quality' => 'hd']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['quality'] === 'hd';
    });
});

it('sends moderation instead of response_format to gpt-image models', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-image-1',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'gpt-image-1'
            && ($body['moderation'] ?? null) === 'low'
            && ! array_key_exists('response_format', $body);
    });
});

it('sends size parameter when provided', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-3',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1792']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['size'] === '1024x1792';
    });
});

it('returns base64 data URLs from generated images', function () {
    $images = (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-3',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    expect($images)->toHaveCount(1)
        ->and($images[0]['url'])->toStartWith('data:image/png;base64,');
});

it('forces response_format=b64_json for dall-e models so we never get a hosted URL that may expire', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-3',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'dall-e-3'
            && ($body['response_format'] ?? null) === 'b64_json';
    });
});

it('does NOT send response_format for gpt-image-1 (always returns base64 and rejects this param)', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-image-1',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'gpt-image-1'
            && ! array_key_exists('response_format', $body);
    });
});

it('ignores an n option, which the image builder has no way to forward', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-2',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024', 'n' => 5]);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return ! array_key_exists('n', $body);
    });
});

it('does NOT send n when not explicitly provided (lets provider use its default)', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-2',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return ! array_key_exists('n', $body);
    });
});

it('returns nothing when the provider answers with a hosted URL instead of base64', function () {
    Http::swap(new Factory);
    Http::fake([
        '*' => Http::response([
            'data' => [[
                'url' => 'https://example.com/dalle-image.png',
            ]],
        ]),
    ]);

    $images = (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-3',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    expect($images)->toBe([]);
});

it('sends moderation instead of response_format to chatgpt-image models', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'chatgpt-image-latest',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'chatgpt-image-latest'
            && ($body['moderation'] ?? null) === 'low'
            && ! array_key_exists('response_format', $body);
    });
});

it('still returns a usable image for a chatgpt-image model', function () {
    $images = (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'chatgpt-image-latest',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    expect($images)->toHaveCount(1)
        ->and($images[0]['url'])->toStartWith('data:image/png;base64,');
});

it('treats every gpt-image naming variant as returning base64 by default', function (string $model, bool $expected) {
    expect(OpenAiImageGateway::returnsBase64ByDefault($model))->toBe($expected);
})->with([
    ['gpt-image-1', true],
    ['gpt-image-1-mini', true],
    ['gpt-image-1.5', true],
    ['chatgpt-image-latest', true],
    ['dall-e-2', false],
    ['dall-e-3', false],
]);
