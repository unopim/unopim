<?php

use Illuminate\Support\Facades\DB;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function platformPayload(array $overrides = []): array
{
    return array_merge([
        'label'    => 'Primary OpenAI',
        'provider' => AiProvider::OpenAI->value,
        'api_url'  => 'https://api.openai.com/v1',
        'api_key'  => 'sk-secret-key',
        'models'   => 'gpt-4o-mini,gpt-4o',
        'status'   => 1,
    ], $overrides);
}

it('forbids platform administration without the platform permission', function (string $method, string $route) {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->json($method, route($route, ['id' => 1]))->assertForbidden();
})->with([
    ['get', 'admin.magic_ai.platform.index'],
    ['post', 'admin.magic_ai.platform.store'],
    ['post', 'admin.magic_ai.platform.test'],
    ['post', 'admin.magic_ai.platform.fetch_models'],
    ['put', 'admin.magic_ai.platform.update'],
    ['delete', 'admin.magic_ai.platform.delete'],
    ['post', 'admin.magic_ai.platform.set_default'],
]);

it('stores a platform with an encrypted api key', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload())->assertOk();

    $platform = MagicAIPlatform::firstWhere('label', 'Primary OpenAI');

    expect($platform->api_key)->toBe('sk-secret-key')
        ->and($platform->getRawOriginal('api_key'))->not->toBe('sk-secret-key');
});

it('rejects a model list containing an unusable model name', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload(['models' => 'gpt-4o, bad model!']))
        ->assertJsonValidationErrors('models');
});

it('strips the tilde marker some gateways prepend before storing model names', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload(['models' => '~llama-3.3-70b']))->assertOk();

    expect(MagicAIPlatform::firstWhere('label', 'Primary OpenAI')->models)->toBe('llama-3.3-70b');
});

it('requires an explicit base url for a custom platform', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload([
        'provider' => AiProvider::Custom->value,
        'api_url'  => '',
    ]))->assertJsonValidationErrors('api_url');
});

it('rejects a base url that points at an internal host', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload(['api_url' => 'http://169.254.169.254/latest']))
        ->assertJsonValidationErrors('api_url');
});

it('rejects extras that would redefine the endpoint or credential', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload([
        'extras' => '{"url":"http://169.254.169.254"}',
    ]))->assertJsonValidationErrors('extras');
});

it('keeps a safe extras payload on the platform', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload([
        'extras' => '{"organization":"org-123"}',
    ]))->assertOk();

    expect(MagicAIPlatform::firstWhere('label', 'Primary OpenAI')->extras)->toBe(['organization' => 'org-123']);
});

it('refuses to mark a disabled platform as the default', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), platformPayload(['status' => 0, 'is_default' => 1]))
        ->assertJsonValidationErrors('is_default');
});

it('masks the stored api key when loading a platform for editing', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->create(['api_key' => 'sk-secret-key']);

    $response = getJson(route('admin.magic_ai.platform.edit', $platform->id))->assertOk();

    expect($response->json('data.api_key'))->toBe('********')
        ->and($response->json('data.api_key_corrupted'))->toBeFalse()
        ->and(json_encode($response->json()))->not->toContain('sk-secret-key');
});

it('reports a corrupted api key instead of failing the edit screen', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->create();

    DB::table('magic_ai_platforms')->where('id', $platform->id)->update(['api_key' => 'not-encrypted']);

    $response = getJson(route('admin.magic_ai.platform.edit', $platform->id))->assertOk();

    expect($response->json('data.api_key_corrupted'))->toBeTrue()
        ->and($response->json('data.api_key'))->toBe('');
});

it('keeps the stored api key when the masked placeholder is submitted back', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->create(['api_key' => 'sk-secret-key']);

    putJson(route('admin.magic_ai.platform.update', $platform->id), platformPayload([
        'label'   => 'Renamed',
        'api_key' => '********',
    ]))->assertOk();

    expect($platform->fresh()->api_key)->toBe('sk-secret-key')
        ->and($platform->fresh()->label)->toBe('Renamed');
});

it('replaces the api key when a new one is submitted', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->create(['api_key' => 'sk-old']);

    putJson(route('admin.magic_ai.platform.update', $platform->id), platformPayload(['api_key' => 'sk-new']))->assertOk();

    expect($platform->fresh()->api_key)->toBe('sk-new');
});

it('returns not found when updating a platform that no longer exists', function () {
    $this->loginWithPermissions('all');

    putJson(route('admin.magic_ai.platform.update', 99999), platformPayload())->assertNotFound();
});

it('refuses to delete the default platform', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->default()->create();

    deleteJson(route('admin.magic_ai.platform.delete', $platform->id))->assertBadRequest();

    expect(MagicAIPlatform::find($platform->id))->not->toBeNull();
});

it('deletes a non default platform', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->create();

    deleteJson(route('admin.magic_ai.platform.delete', $platform->id))->assertOk();

    expect(MagicAIPlatform::find($platform->id))->toBeNull();
});

it('moves the default flag to the chosen platform', function () {
    $this->loginWithPermissions('all');

    $current = MagicAIPlatform::factory()->default()->create();
    $next = MagicAIPlatform::factory()->create();

    postJson(route('admin.magic_ai.platform.set_default', $next->id))->assertOk();

    expect($next->fresh()->is_default)->toBeTrue()
        ->and($current->fresh()->is_default)->toBeFalse();
});

it('refuses to make a disabled platform the default', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->disabled()->create();

    postJson(route('admin.magic_ai.platform.set_default', $platform->id))->assertUnprocessable();

    expect($platform->fresh()->is_default)->toBeFalse();
});
