<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

it('should reject marking a disabled platform as default when creating', function () {
    $this->loginAsAdmin();

    $response = $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'      => 'Disabled Default',
        'provider'   => 'openai',
        'api_url'    => 'https://example.com',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4',
        'is_default' => 1,
        'status'     => 0,
    ]);

    $response->assertStatus(422);

    $this->assertDatabaseMissing((new MagicAIPlatform)->getTable(), ['label' => 'Disabled Default']);
});

it('should reject marking a platform as default while disabling it in update', function () {
    $this->loginAsAdmin();

    $platform = MagicAIPlatform::create([
        'label'      => 'Initial Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://example.com',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4',
        'status'     => true,
        'is_default' => true,
    ]);

    $response = $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
        'label'      => $platform->label,
        'provider'   => $platform->provider,
        'api_url'    => $platform->api_url,
        'models'     => $platform->models,
        'is_default' => 1,
        'status'     => 0,
    ]);

    $response->assertStatus(422);
});
