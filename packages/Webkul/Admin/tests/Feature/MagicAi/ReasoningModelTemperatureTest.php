<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\LaravelAiAdapter;

/**
 * Reasoning models (o-series, gpt-5*) reject `temperature` — only the default
 * is allowed. Sending temperature triggers a 400 invalid_request_error.
 * This test pins the LaravelAiAdapter behaviour so a regression breaks loudly.
 */
beforeEach(function () {
    $this->platform = MagicAIPlatform::create([
        'label'    => 'Test OpenAI',
        'provider' => 'openai',
        'api_key'  => 'sk-test',
        'models'   => json_encode(['gpt-4o-mini', 'o1-mini', 'gpt-5.2-pro']),
        'status'   => true,
    ]);

    // Prism uses OpenAI's Responses API format (output[]), not the legacy Chat Completions choices[].
    Http::fake([
        '*' => Http::response([
            'id'     => 'resp_test',
            'model'  => 'gpt-4o-mini',
            'status' => 'completed',
            'output' => [[
                'type'    => 'message',
                'status'  => 'completed',
                'role'    => 'assistant',
                'content' => [['type' => 'output_text', 'text' => 'ok']],
            ]],
            'usage' => ['input_tokens' => 1, 'output_tokens' => 1, 'total_tokens' => 2],
        ]),
    ]);
});

it('sends temperature for non-reasoning models', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-4o-mini',
        prompt: 'hi',
        temperature: 0.42,
    ))->ask();

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'gpt-4o-mini'
            && ($body['temperature'] ?? null) === 0.42;
    });
});

it('does NOT send temperature for o-series reasoning models', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'o1-mini',
        prompt: 'hi',
        temperature: 0.42,
    ))->ask();

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'o1-mini'
            && ! array_key_exists('temperature', $body);
    });
});

it('does NOT send temperature for gpt-5* reasoning models', function () {
    (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-5.2-pro',
        prompt: 'hi',
        temperature: 0.42,
    ))->ask();

    Http::assertSent(function (Request $request) {
        $body = json_decode($request->body(), true);

        return $body['model'] === 'gpt-5.2-pro'
            && ! array_key_exists('temperature', $body);
    });
});
