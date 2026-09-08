<?php

use Illuminate\Support\Facades\DB;

it('denies test-connection without the ai-agent.platform permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => 'openai',
        'models'   => 'gpt-4o',
    ])->assertForbidden();
});

it('denies fetch-models without the ai-agent.platform permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
        'provider' => 'openai',
    ])->assertForbidden();
});

it('blocks an SSRF api_url pointing at the cloud metadata host on test-connection', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => 'custom',
        'api_url'  => 'http://169.254.169.254/latest/meta-data/',
        'api_key'  => 'x',
        'models'   => 'gpt-4o',
    ])->assertStatus(422);
});

it('blocks an SSRF api_url pointing at the cloud metadata host on fetch-models', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.fetch_models'), [
        'provider' => 'custom',
        'api_url'  => 'http://169.254.169.254/',
    ])->assertStatus(422);
});

it('blocks an extras.url override that points at an internal host on test-connection', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => 'openai',
        'api_url'  => 'https://api.openai.com/v1',
        'api_key'  => 'sk-test',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['url' => 'http://127.0.0.1:8080/']),
    ])->assertStatus(422);
});

it('blocks an extras.url override pointing at cloud metadata on test-connection', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.test'), [
        'provider' => 'openai',
        'api_url'  => 'https://api.openai.com/v1',
        'api_key'  => 'sk-test',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['url' => 'http://169.254.169.254/latest/meta-data/']),
    ])->assertStatus(422);
});

it('rejects a platform saved with an unsafe extras.url override', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'Malicious',
        'provider' => 'openai',
        'api_url'  => 'https://api.openai.com/v1',
        'api_key'  => 'sk-test',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['url' => 'http://127.0.0.1:8080/']),
    ])->assertStatus(422);

    expect(DB::table('magic_ai_platforms')->where('label', 'Malicious')->exists())->toBeFalse();
});
