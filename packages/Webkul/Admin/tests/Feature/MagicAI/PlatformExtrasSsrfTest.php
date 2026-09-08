<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Services\ProviderOverrides;

/*
 * Regression cover for the SSRF reported against test-connection: the `extras`
 * JSON was merged over the provider overrides after SafeWebhookUrl had checked
 * `api_url`, so `extras.url` replaced the validated endpoint.
 */
it('does not let extras.url override the validated api_url', function () {
    $this->loginAsAdmin();

    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'OK']]]], 200)]);

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => AiProvider::Custom->value,
        'api_key'  => 'sk-test-key-1234567890',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['url' => 'http://127.0.0.1:8080/']),
    ]);

    $hosts = collect(Http::recorded())->map(fn ($pair) => $pair[0]->toPsrRequest()->getUri()->getHost());

    expect($hosts)->not->toContain('127.0.0.1');
});

it('rejects a reserved key in extras on test-connection', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => AiProvider::Custom->value,
        'api_key'  => 'sk-test-key-1234567890',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['url' => 'http://169.254.169.254/']),
    ])->assertStatus(422)->assertJsonValidationErrors('extras');
});

it('does not persist a reserved key from extras', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.store'), [
        'provider' => AiProvider::Custom->value,
        'label'    => 'ssrf',
        'api_key'  => 'sk-test-key-1234567890',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['url' => 'http://169.254.169.254/latest/meta-data/']),
    ])->assertStatus(422);

    expect(DB::table('magic_ai_platforms')->where('label', 'ssrf')->exists())->toBeFalse();
});

it('keeps legitimate extras keys', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.store'), [
        'provider' => AiProvider::Custom->value,
        'label'    => 'org-scoped',
        'api_key'  => 'sk-test-key-1234567890',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['organization' => 'org-abc']),
    ])->assertOk();

    $extras = DB::table('magic_ai_platforms')->where('label', 'org-scoped')->value('extras');

    expect($extras)->toContain('org-abc');
});

it('strips reserved keys from a platform row persisted before the guard existed', function () {
    $overrides = ProviderOverrides::build(
        ['key' => 'sk-real', 'url' => 'https://api.openai.com/v1'],
        ['url' => 'http://127.0.0.1:8080/', 'organization' => 'org-abc'],
    );

    expect($overrides['url'])->toBe('https://api.openai.com/v1')
        ->and($overrides['organization'])->toBe('org-abc');
});
