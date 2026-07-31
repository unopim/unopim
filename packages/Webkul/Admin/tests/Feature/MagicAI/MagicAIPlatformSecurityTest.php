<?php

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
