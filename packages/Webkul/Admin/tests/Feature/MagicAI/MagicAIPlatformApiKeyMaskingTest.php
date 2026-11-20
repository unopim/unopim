<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

const STORED_PLATFORM_KEY = 'sk-test-StoredKeyThatMustNeverBeEchoed0123';

function createPlatformWithStoredKey(): MagicAIPlatform
{
    return MagicAIPlatform::create([
        'label'      => 'Masked Key Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => STORED_PLATFORM_KEY,
        'models'     => 'gpt-4o-mini',
        'status'     => true,
        'is_default' => false,
    ]);
}

it('renders the platform api key field as a password input', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.magic_ai.platform.index'));

    $response->assertOk();
    expect($response->getContent())->toMatch('/<input[^>]*type="password"[^>]*name="api_key"/s');
});

it('masks the stored api key when a platform is opened for editing', function () {
    $this->loginAsAdmin();

    $platform = createPlatformWithStoredKey();

    $response = $this->getJson(route('admin.magic_ai.platform.edit', $platform->id));

    $response->assertOk()
        ->assertJsonPath('data.api_key', '********');

    expect($response->getContent())->not->toContain(STORED_PLATFORM_KEY);
});

it('never echoes a stored api key on the platforms page or its grid', function () {
    $this->loginAsAdmin();

    createPlatformWithStoredKey();

    expect($this->get(route('admin.magic_ai.platform.index'))->getContent())
        ->not->toContain(STORED_PLATFORM_KEY);

    expect($this->getJson(route('admin.magic_ai.platform.index'))->getContent())
        ->not->toContain(STORED_PLATFORM_KEY);
});
