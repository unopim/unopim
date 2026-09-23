<?php

use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\ManagedPlatform;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

const MANAGED_KEY = 'sk-managed-account';

beforeEach(function () {
    config([
        'magic_ai.managed.provider' => AiProvider::Concentrate->value,
        'magic_ai.managed.label'    => 'Concentrate AI',
        'magic_ai.managed.api_url'  => null,
        'magic_ai.managed.api_key'  => MANAGED_KEY,
        'magic_ai.managed.models'   => ['gpt-oss-120b', 'gpt-oss-20b'],
    ]);
});

function managedPayload(array $overrides = []): array
{
    return array_merge([
        'label'    => 'Managed '.uniqid(),
        'provider' => AiProvider::Concentrate->value,
        'api_key'  => MANAGED_KEY,
        'models'   => 'gpt-oss-120b,gpt-oss-20b',
        'status'   => 1,
    ], $overrides);
}

function managedPlatformRecord(): MagicAIPlatform
{
    return MagicAIPlatform::create([
        'label'    => 'Concentrate AI',
        'provider' => AiProvider::Concentrate->value,
        'api_key'  => MANAGED_KEY,
        'models'   => 'gpt-oss-120b,gpt-oss-20b',
        'status'   => true,
    ]);
}

it('saves the managed key with its allowed models', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), managedPayload())->assertOk();
});

it('rejects a model outside the managed list on the managed key', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), managedPayload(['models' => 'gpt-oss-120b,gpt-5.1']))
        ->assertJsonValidationErrors('models');
});

it('keeps the managed key on its own provider and endpoint', function (array $overrides) {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), managedPayload($overrides))
        ->assertJsonValidationErrors('api_key');
})->with([
    'another provider' => [['provider' => AiProvider::OpenAI->value]],
    'another endpoint' => [['api_url' => 'https://collector.example.com/v1']],
    'provider extras'  => [['extras' => json_encode(['organization' => 'org-1'])]],
]);

it('accepts the managed endpoint written out in full', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), managedPayload(['api_url' => AiProvider::Concentrate->defaultUrl().'/']))
        ->assertOk();
});

it('lets a client Concentrate key use any model', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), managedPayload([
        'api_key' => 'sk-client-own-key',
        'models'  => 'gpt-5.1,claude-opus-5',
    ]))->assertOk();
});

it('leaves other providers unrestricted', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), managedPayload([
        'provider' => AiProvider::OpenAI->value,
        'api_key'  => 'sk-openai-key',
        'models'   => 'gpt-5.1,gpt-4o-mini',
    ]))->assertOk();
});

it('restricts nothing when no managed key is configured', function () {
    config(['magic_ai.managed.api_key' => null]);

    expect(app(ManagedPlatform::class)->violations('', AiProvider::Concentrate->value, null, ['gpt-5.1']))->toBe([]);
});

it('guards the stored managed key when the form submits it masked', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload([
        'api_key' => '********',
        'api_url' => 'https://collector.example.com/v1',
        'models'  => 'gpt-5.1',
    ]))->assertJsonValidationErrors(['api_key', 'models']);

    expect($platform->fresh()->api_url)->toBeNull()
        ->and($platform->fresh()->models)->toBe('gpt-oss-120b,gpt-oss-20b');
});

it('lifts the restriction once the client replaces the managed key', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload([
        'api_key' => 'sk-client-own-key',
        'models'  => 'gpt-5.1',
    ]))->assertOk();

    expect($platform->fresh()->models)->toBe('gpt-5.1');
});

it('never sends the stored managed key to another endpoint on a connection test', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    postJson(route('admin.magic_ai.platform.test'), [
        'id'       => $platform->id,
        'provider' => AiProvider::Custom->value,
        'api_key'  => '********',
        'api_url'  => 'https://collector.example.com/v1',
        'models'   => 'gpt-oss-120b',
    ])->assertJsonValidationErrors('api_key');
});

it('offers only the managed models on a fetch without calling the provider', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    postJson(route('admin.magic_ai.platform.fetch_models'), [
        'id'       => $platform->id,
        'provider' => AiProvider::Concentrate->value,
        'api_key'  => '********',
    ])
        ->assertOk()
        ->assertJson([
            'models'      => ['gpt-oss-120b', 'gpt-oss-20b'],
            'recommended' => ['gpt-oss-120b', 'gpt-oss-20b'],
            'api_url'     => AiProvider::Concentrate->defaultUrl(),
        ]);
});

it('swaps a model outside the managed list at generation time', function () {
    $managedPlatform = app(ManagedPlatform::class);

    expect($managedPlatform->resolveModel(managedPlatformRecord(), 'gpt-5.1'))->toBe('gpt-oss-120b')
        ->and($managedPlatform->resolveModel(managedPlatformRecord(), 'gpt-oss-20b'))->toBe('gpt-oss-20b');

    $clientPlatform = MagicAIPlatform::factory()->create(['provider' => AiProvider::Concentrate->value, 'api_key' => 'sk-client-own-key']);

    expect($managedPlatform->resolveModel($clientPlatform, 'gpt-5.1'))->toBe('gpt-5.1');
});

it('provisions the managed platform once and makes it the default', function () {
    MagicAIPlatform::query()->update(['is_default' => false]);

    artisan('unopim:magic-ai:managed-platform')->assertSuccessful();
    artisan('unopim:magic-ai:managed-platform')->assertSuccessful();

    $platforms = MagicAIPlatform::where('provider', AiProvider::Concentrate->value)->get()
        ->filter(fn (MagicAIPlatform $platform): bool => $platform->safeApiKey() === MANAGED_KEY);

    expect($platforms)->toHaveCount(1)
        ->and($platforms->first()->is_default)->toBeTrue()
        ->and($platforms->first()->models)->toBe('gpt-oss-120b,gpt-oss-20b')
        ->and($platforms->first()->api_url)->toBe(AiProvider::Concentrate->defaultUrl());
});

it('refuses to provision without a managed key', function () {
    config(['magic_ai.managed.api_key' => null]);

    artisan('unopim:magic-ai:managed-platform')->assertFailed();
});
