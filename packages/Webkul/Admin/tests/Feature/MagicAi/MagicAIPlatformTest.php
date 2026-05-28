<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return the platform index page', function () {
    $this->get(route('admin.magic_ai.platform.index'))
        ->assertOk();
});

it('should return the platform DataGrid as JSON for AJAX requests', function () {
    MagicAIPlatform::create([
        'label'      => 'Test Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'models'     => 'gpt-4o,gpt-4o-mini',
        'is_default' => true,
        'status'     => true,
    ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.magic_ai.platform.index'));

    $response->assertOk();

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);
});

it('should return validation errors when creating platform with missing fields', function () {
    $this->postJson(route('admin.magic_ai.platform.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['label', 'provider', 'models']);
});

it('should create a new platform successfully', function () {
    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'      => 'My OpenAI Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'sk-test-key-123',
        'models'     => 'gpt-4o,gpt-4o-mini',
        'is_default' => true,
        'status'     => true,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.configuration.platform.message.save-success'),
        ]);

    $this->assertDatabaseHas('magic_ai_platforms', [
        'label'    => 'My OpenAI Platform',
        'provider' => 'openai',
        'models'   => 'gpt-4o,gpt-4o-mini',
    ]);
});

it('should create platform with default status true when status is not provided', function () {
    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'Auto Status Platform',
        'provider' => 'groq',
        'models'   => 'llama-3.1-70b-versatile',
    ])
        ->assertOk();

    $this->assertDatabaseHas('magic_ai_platforms', [
        'label'  => 'Auto Status Platform',
        'status' => true,
    ]);
});

it('should create platform with extras field', function () {
    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'Azure Platform',
        'provider' => 'azure',
        'models'   => 'gpt-4o',
        'extras'   => json_encode(['deployment' => 'my-deployment']),
    ])
        ->assertOk();

    $platform = MagicAIPlatform::where('label', 'Azure Platform')->first();

    $this->assertNotNull($platform);
    $this->assertEquals(['deployment' => 'my-deployment'], $platform->extras);
});

it('should return validation error for invalid model names', function () {
    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'Bad Model Platform',
        'provider' => 'openai',
        'models'   => 'valid-model, ,another model with spaces!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['models']);
});

it('should return validation error for invalid api_url', function () {
    $this->postJson(route('admin.magic_ai.platform.store'), [
        'label'    => 'Invalid URL Platform',
        'provider' => 'openai',
        'api_url'  => 'not-a-url',
        'models'   => 'gpt-4o',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['api_url']);
});

it('should return platform data for edit', function () {
    $platform = MagicAIPlatform::create([
        'label'      => 'Edit Test Platform',
        'provider'   => 'gemini',
        'api_url'    => 'https://generativelanguage.googleapis.com/v1beta',
        'api_key'    => 'test-api-key',
        'models'     => 'gemini-2.5-pro',
        'is_default' => false,
        'status'     => true,
    ]);

    $response = $this->getJson(route('admin.magic_ai.platform.edit', $platform->id))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'label',
                'provider',
                'api_url',
                'api_key',
                'models',
                'is_default',
                'status',
            ],
        ]);

    $data = $response->json('data');

    $this->assertEquals('Edit Test Platform', $data['label']);
    $this->assertEquals('gemini', $data['provider']);
    $this->assertEquals('********', $data['api_key']);
});

it('should mask api_key in edit response', function () {
    $platform = MagicAIPlatform::create([
        'label'    => 'Masked Key Test',
        'provider' => 'openai',
        'api_key'  => 'sk-secret-key-12345',
        'models'   => 'gpt-4o',
        'status'   => true,
    ]);

    $data = $this->getJson(route('admin.magic_ai.platform.edit', $platform->id))
        ->json('data');

    $this->assertEquals('********', $data['api_key']);
});

it('should return empty api_key in edit when no key is stored', function () {
    $platform = MagicAIPlatform::create([
        'label'    => 'No Key Platform',
        'provider' => 'ollama',
        'models'   => 'llama3',
        'status'   => true,
    ]);

    $data = $this->getJson(route('admin.magic_ai.platform.edit', $platform->id))
        ->json('data');

    $this->assertEquals('', $data['api_key']);
});

it('should update a platform successfully', function () {
    $platform = MagicAIPlatform::create([
        'label'    => 'Original Label',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
        'status'   => true,
    ]);

    $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
        'label'    => 'Updated Label',
        'provider' => 'openai',
        'models'   => 'gpt-4o,gpt-4o-mini',
        'status'   => true,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.configuration.platform.message.update-success'),
        ]);

    $this->assertDatabaseHas('magic_ai_platforms', [
        'id'     => $platform->id,
        'label'  => 'Updated Label',
        'models' => 'gpt-4o,gpt-4o-mini',
    ]);
});

it('should not update api_key when masked value is sent', function () {
    $platform = MagicAIPlatform::create([
        'label'    => 'Key Retain Test',
        'provider' => 'openai',
        'api_key'  => 'sk-original-key',
        'models'   => 'gpt-4o',
        'status'   => true,
    ]);

    $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
        'label'    => 'Key Retain Test',
        'provider' => 'openai',
        'api_key'  => '********',
        'models'   => 'gpt-4o',
        'status'   => true,
    ])
        ->assertOk();

    $updated = MagicAIPlatform::find($platform->id);

    $this->assertNotEquals('********', $updated->api_key);
});

it('should return validation errors when updating with missing fields', function () {
    $platform = MagicAIPlatform::create([
        'label'    => 'Validation Test',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
        'status'   => true,
    ]);

    $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['label', 'provider', 'models']);
});

it('should delete a platform successfully', function () {
    $platform = MagicAIPlatform::create([
        'label'      => 'Delete Me',
        'provider'   => 'groq',
        'models'     => 'llama-3.1-70b',
        'is_default' => false,
        'status'     => true,
    ]);

    $this->deleteJson(route('admin.magic_ai.platform.delete', $platform->id))
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.configuration.platform.message.delete-success'),
        ]);

    $this->assertDatabaseMissing('magic_ai_platforms', [
        'id' => $platform->id,
    ]);
});

it('should not delete the only default active platform', function () {
    // Ensure no other active platforms exist
    MagicAIPlatform::query()->delete();

    $platform = MagicAIPlatform::create([
        'label'      => 'Only Default',
        'provider'   => 'openai',
        'models'     => 'gpt-4o',
        'is_default' => true,
        'status'     => true,
    ]);

    $this->deleteJson(route('admin.magic_ai.platform.delete', $platform->id))
        ->assertStatus(400)
        ->assertJsonFragment([
            'message' => trans('admin::app.configuration.platform.message.cannot-delete-default'),
        ]);

    $this->assertDatabaseHas('magic_ai_platforms', [
        'id' => $platform->id,
    ]);
});

it('should set a platform as default', function () {
    $platform1 = MagicAIPlatform::create([
        'label'      => 'Platform One',
        'provider'   => 'openai',
        'models'     => 'gpt-4o',
        'is_default' => true,
        'status'     => true,
    ]);

    $platform2 = MagicAIPlatform::create([
        'label'      => 'Platform Two',
        'provider'   => 'groq',
        'models'     => 'llama-3.1-70b',
        'is_default' => false,
        'status'     => true,
    ]);

    $this->postJson(route('admin.magic_ai.platform.set_default', $platform2->id))
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.configuration.platform.message.set-default-success'),
        ]);

    $this->assertDatabaseHas('magic_ai_platforms', [
        'id'         => $platform2->id,
        'is_default' => true,
    ]);

    $this->assertDatabaseHas('magic_ai_platforms', [
        'id'         => $platform1->id,
        'is_default' => false,
    ]);
});

it('should unset previous default when setting new default', function () {
    MagicAIPlatform::query()->delete();

    $old = MagicAIPlatform::create([
        'label'      => 'Old Default',
        'provider'   => 'openai',
        'models'     => 'gpt-4o',
        'is_default' => true,
        'status'     => true,
    ]);

    $new = MagicAIPlatform::create([
        'label'      => 'New Default',
        'provider'   => 'gemini',
        'models'     => 'gemini-2.5-pro',
        'is_default' => false,
        'status'     => true,
    ]);

    $this->postJson(route('admin.magic_ai.platform.set_default', $new->id))
        ->assertOk();

    $this->assertFalse(MagicAIPlatform::find($old->id)->is_default);
    $this->assertTrue(MagicAIPlatform::find($new->id)->is_default);
});

it('should create platform with multiple providers', function () {
    $providers = ['openai', 'groq', 'gemini', 'anthropic', 'ollama'];

    foreach ($providers as $provider) {
        $this->postJson(route('admin.magic_ai.platform.store'), [
            'label'    => ucfirst($provider).' Platform',
            'provider' => $provider,
            'models'   => 'test-model',
        ])
            ->assertOk();
    }

    foreach ($providers as $provider) {
        $this->assertDatabaseHas('magic_ai_platforms', [
            'provider' => $provider,
        ]);
    }
});

it('should return 404 for non-existent platform edit', function () {
    $this->getJson(route('admin.magic_ai.platform.edit', 99999))
        ->assertNotFound();
});

it('should return 404 for non-existent platform update', function () {
    $this->putJson(route('admin.magic_ai.platform.update', 99999), [
        'label'    => 'Ghost',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
    ])
        ->assertNotFound();
});

it('should return 404 for non-existent platform set default', function () {
    $this->postJson(route('admin.magic_ai.platform.set_default', 99999))
        ->assertNotFound();
});

it('should return only image-capable models when purpose is image_generation', function () {
    MagicAIPlatform::query()->delete();

    MagicAIPlatform::create([
        'label'      => 'OpenAI Platform',
        'provider'   => 'openai',
        'models'     => 'gpt-4o,dall-e-3,gpt-image-1,gpt-5.4',
        'is_default' => true,
        'status'     => true,
    ]);

    $response = $this->getJson(route('admin.magic_ai.platforms', ['purpose' => 'image_generation']))
        ->assertOk();

    $platforms = $response->json('platforms');

    $this->assertNotEmpty($platforms);

    $models = $platforms[0]['models'];

    $this->assertContains('dall-e-3', $models);
    $this->assertContains('gpt-image-1', $models);
    $this->assertNotContains('gpt-4o', $models);
    $this->assertNotContains('gpt-5.4', $models);
});

it('should exclude platforms without image models when purpose is image_generation', function () {
    MagicAIPlatform::query()->delete();

    MagicAIPlatform::create([
        'label'      => 'Anthropic Platform',
        'provider'   => 'anthropic',
        'models'     => 'claude-sonnet-4-20250514,claude-haiku-4-20250414',
        'is_default' => true,
        'status'     => true,
    ]);

    $response = $this->getJson(route('admin.magic_ai.platforms', ['purpose' => 'image_generation']))
        ->assertOk();

    $platforms = $response->json('platforms');

    $this->assertEmpty($platforms);
});

it('should return all models when no purpose param is provided', function () {
    MagicAIPlatform::query()->delete();

    MagicAIPlatform::create([
        'label'      => 'OpenAI Platform',
        'provider'   => 'openai',
        'models'     => 'gpt-4o,dall-e-3,gpt-image-1',
        'is_default' => true,
        'status'     => true,
    ]);

    $response = $this->getJson(route('admin.magic_ai.platforms'))
        ->assertOk();

    $platforms = $response->json('platforms');
    $models = $platforms[0]['models'];

    $this->assertContains('gpt-4o', $models);
    $this->assertContains('dall-e-3', $models);
    $this->assertContains('gpt-image-1', $models);
});

it('should set status to false on update when status is not provided', function () {
    $platform = MagicAIPlatform::create([
        'label'    => 'Status Toggle Test',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
        'status'   => true,
    ]);

    $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
        'label'    => 'Status Toggle Test',
        'provider' => 'openai',
        'models'   => 'gpt-4o',
    ])
        ->assertOk();

    $this->assertDatabaseHas('magic_ai_platforms', [
        'id'     => $platform->id,
        'status' => false,
    ]);
});
