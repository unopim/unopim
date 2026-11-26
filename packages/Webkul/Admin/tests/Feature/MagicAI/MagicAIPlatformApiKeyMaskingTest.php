<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

beforeEach(function (): void {
    $this->loginAsAdmin();

    $this->storedKey = 'sk-test-StoredKeyThatMustNeverBeEchoed0123';

    $this->platform = MagicAIPlatform::create([
        'label'      => 'Masked Key Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => $this->storedKey,
        'models'     => 'gpt-4o-mini',
        'status'     => true,
        'is_default' => false,
    ]);
});

it('renders the platform api key field as a password input', function (): void {
    $response = $this->get(route('admin.magic_ai.platform.index'));

    $response->assertOk();
    expect($response->getContent())->toMatch('/<input[^>]*type="password"[^>]*name="api_key"/s');
});

it('masks the stored api key when a platform is opened for editing', function (): void {
    $response = $this->getJson(route('admin.magic_ai.platform.edit', $this->platform->id));

    $response->assertOk()
        ->assertJsonPath('data.api_key', '********');

    expect($response->getContent())->not->toContain($this->storedKey);
});

it('never echoes a stored api key on the platforms page or its grid', function (): void {
    expect($this->get(route('admin.magic_ai.platform.index'))->getContent())
        ->not->toContain($this->storedKey);

    expect($this->getJson(route('admin.magic_ai.platform.index'))->getContent())
        ->not->toContain($this->storedKey);
});
