<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

it('should reject deletion of the default Magic AI platform (Issue #720)', function () {
    $this->loginAsAdmin();

    MagicAIPlatform::query()->update(['is_default' => false]);

    $other = MagicAIPlatform::create([
        'label'      => 'Other',
        'provider'   => 'openai',
        'api_url'    => 'https://example.test',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4',
        'status'     => true,
        'is_default' => false,
    ]);

    $default = MagicAIPlatform::create([
        'label'      => 'Default Platform',
        'provider'   => 'groq',
        'api_url'    => 'https://example.test',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4',
        'status'     => true,
        'is_default' => true,
    ]);

    $response = $this->deleteJson(route('admin.magic_ai.platform.delete', $default->id));

    $response->assertStatus(400);
    $this->assertDatabaseHas($default->getTable(), ['id' => $default->id]);
});
