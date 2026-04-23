<?php

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\LaravelAiAdapter;

/**
 * Verifies LaravelAiAdapter sends only explicitly-set image options to OpenAI.
 *
 * Regression test for issue #699 — Laravel AI SDK was hardcoding
 * `quality: auto` and `moderation: low` for all OpenAI models, which
 * DALL-E 2 and DALL-E 3 reject as unknown parameters.
 */
beforeEach(function () {
    $this->platform = MagicAIPlatform::create([
        'label'    => 'Test OpenAI',
        'provider' => 'openai',
        'api_key'  => 'sk-test',
        'models'   => json_encode(['dall-e-2', 'dall-e-3', 'gpt-image-1']),
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

it('does not send moderation to gpt-image models when not explicitly set', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-image-1',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024']);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'gpt-image-1'
            && ! array_key_exists('moderation', $body);
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

it('forwards the n parameter when explicitly set', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'dall-e-2',
        prompt: 'a red apple',
    ))->images(['size' => '1024x1024', 'n' => 5]);

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return ($body['n'] ?? null) === 5;
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

it('returns the hosted URL when the provider response has no base64 (DALL-E url-mode fallback)', function () {
    // Replace the beforeEach-registered fake (b64_json) with a URL-only response.
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

    expect($images)->toHaveCount(1)
        ->and($images[0]['url'])->toBe('https://example.com/dalle-image.png');
});
