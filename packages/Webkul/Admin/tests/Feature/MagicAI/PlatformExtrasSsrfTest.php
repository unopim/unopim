<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
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

it('strips a reserved key whose case differs from the canonical spelling', function () {
    $overrides = ProviderOverrides::build(
        ['key' => 'sk-real', 'url' => 'https://api.openai.com/v1'],
        ['URL' => 'http://169.254.169.254/', 'Api_Key' => 'sk-attacker', 'organization' => 'org-abc'],
    );

    expect($overrides['url'])->toBe('https://api.openai.com/v1')
        ->and($overrides['key'])->toBe('sk-real')
        ->and($overrides)->not->toHaveKey('URL')
        ->and($overrides)->not->toHaveKey('Api_Key')
        ->and($overrides['organization'])->toBe('org-abc');
});

it('rejects extras that decode to a json list rather than an object', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.store'), [
        'provider' => AiProvider::Custom->value,
        'label'    => 'list-extras',
        'api_key'  => 'sk-test-key-1234567890',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['url', 'key']),
    ])->assertJsonValidationErrors('extras');

    expect(DB::table('magic_ai_platforms')->where('label', 'list-extras')->exists())->toBeFalse();
});

it('updates saved extras when they are explicitly supplied', function (mixed $extras, array $expected) {
    $this->loginAsAdmin();

    $platform = MagicAIPlatform::create([
        'label'    => 'clear-extras',
        'provider' => AiProvider::OpenAI->value,
        'models'   => 'gpt-4o',
        'extras'   => ['organization' => 'org-old'],
    ]);

    $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
        'label'    => $platform->label,
        'provider' => $platform->provider,
        'models'   => $platform->models,
        'extras'   => $extras,
    ])->assertOk();

    expect($platform->refresh()->extras)->toBe($expected);
})->with([
    'empty object'      => ['{}', []],
    'empty string'      => ['', []],
    'null'              => [null, []],
    'empty array'       => [[], []],
    'replacement JSON'  => ['{"organization":"org-new"}', ['organization' => 'org-new']],
    'replacement array' => [['organization' => 'org-new'], ['organization' => 'org-new']],
]);

it('preserves saved extras when the update omits them', function () {
    $this->loginAsAdmin();

    $platform = MagicAIPlatform::create([
        'label'    => 'keep-extras',
        'provider' => AiProvider::OpenAI->value,
        'models'   => 'gpt-4o',
        'extras'   => ['organization' => 'org-old'],
    ]);

    $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
        'label'    => 'renamed-platform',
        'provider' => $platform->provider,
        'models'   => $platform->models,
    ])->assertOk();

    expect($platform->refresh()->extras)->toBe(['organization' => 'org-old']);
    $this->assertDatabaseHas('magic_ai_platforms', ['id' => $platform->id, 'label' => 'renamed-platform']);
});

it('rejects oversized and deeply nested extras in either input format', function (mixed $extras, string $message) {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'invalid-extras',
        'provider' => AiProvider::OpenAI->value,
        'models'   => 'gpt-4o',
        'extras'   => $extras,
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'extras' => trans('admin::app.configuration.platform.message.'.$message),
    ]);

    $this->assertDatabaseMissing('magic_ai_platforms', ['label' => 'invalid-extras']);
})->with([
    'oversized JSON'  => [json_encode(['organization' => str_repeat('x', 8192)]), 'extras-too-large'],
    'oversized array' => [['organization' => str_repeat('x', 8192)], 'extras-too-large'],
    'deep JSON'       => ['{"options":{"a":{"b":{"c":{"d":{"e":true}}}}}}', 'extras-invalid-json'],
    'deep array'      => [['options' => ['a' => ['b' => ['c' => ['d' => ['e' => true]]]]]], 'extras-invalid-json'],
]);

it('accepts bounded extras in either input format', function (mixed $extras) {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'valid-extras',
        'provider' => AiProvider::OpenAI->value,
        'models'   => 'gpt-4o',
        'extras'   => $extras,
    ])->assertOk();

    expect(MagicAIPlatform::where('label', 'valid-extras')->firstOrFail()->extras)
        ->toEqual(['organization' => 'org-example', 'options' => ['timeout' => 30]]);
})->with([
    'JSON'  => ['{"organization":"org-example","options":{"timeout":30}}'],
    'array' => [['organization' => 'org-example', 'options' => ['timeout' => 30]]],
]);
