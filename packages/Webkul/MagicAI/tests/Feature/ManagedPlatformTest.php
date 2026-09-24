<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\ManagedPlatform;
use Webkul\MagicAI\Support\ModelRecommender;

use function Pest\Laravel\artisan;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

const MANAGED_KEY = 'sk-managed-account';

beforeEach(function () {
    config([
        'magic_ai.managed.provider' => AiProvider::Concentrate->value,
        'magic_ai.managed.label'    => 'Concentrate AI',
        'magic_ai.managed.api_url'  => null,
        'magic_ai.managed.models'   => ['gpt-oss-120b', 'gpt-oss-20b'],
    ]);
});

function managedPayload(array $overrides = []): array
{
    return array_merge([
        'label'    => 'Managed '.uniqid(),
        'provider' => AiProvider::Concentrate->value,
        'api_key'  => '********',
        'models'   => 'gpt-oss-120b,gpt-oss-20b',
        'status'   => 1,
    ], $overrides);
}

function managedPlatformRecord(): MagicAIPlatform
{
    return MagicAIPlatform::factory()->managed()->create([
        'label'    => 'Concentrate AI',
        'provider' => AiProvider::Concentrate->value,
        'api_url'  => null,
        'api_key'  => MANAGED_KEY,
        'models'   => 'gpt-oss-120b,gpt-oss-20b',
    ]);
}

function provisionManagedPlatform(string $answer = MANAGED_KEY): void
{
    artisan('unopim:magic-ai:managed-platform')
        ->expectsQuestion(trans('admin::app.configuration.platform.message.managed-key-prompt'), $answer)
        ->assertSuccessful();
}

it('never lets a request set the managed flag', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.store'), managedPayload([
        'label'      => 'Client platform',
        'api_key'    => 'sk-client-own-key',
        'is_managed' => 1,
    ]))->assertOk();

    $platform = MagicAIPlatform::where('label', 'Client platform')->firstOrFail();

    expect($platform->is_managed)->toBeFalse();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload(['is_managed' => 1]))->assertOk();

    expect($platform->fresh()->is_managed)->toBeFalse();
});

it('stores the key encrypted and flags the platform when provisioning', function () {
    provisionManagedPlatform();

    $platform = MagicAIPlatform::where('is_managed', true)->sole();

    $storedKey = DB::table('magic_ai_platforms')->where('id', $platform->id)->value('api_key');

    expect($storedKey)->not->toBe(MANAGED_KEY)
        ->and(Crypt::decryptString($storedKey))->toBe(MANAGED_KEY)
        ->and($platform->provider)->toBe(AiProvider::Concentrate->value)
        ->and($platform->api_url)->toBe(AiProvider::Concentrate->defaultUrl())
        ->and($platform->models)->toBe('gpt-oss-120b,gpt-oss-20b')
        ->and($platform->extras)->toBeNull()
        ->and($platform->status)->toBeTrue();
});

it('provisions one managed platform and keeps its key on an empty answer', function () {
    provisionManagedPlatform();
    provisionManagedPlatform('');

    $platform = MagicAIPlatform::where('is_managed', true)->sole();

    expect($platform->safeApiKey())->toBe(MANAGED_KEY);

    provisionManagedPlatform('sk-rotated-key');

    expect(MagicAIPlatform::where('is_managed', true)->sole()->safeApiKey())->toBe('sk-rotated-key');
});

it('makes the managed platform the default only when none is set', function () {
    MagicAIPlatform::query()->update(['is_default' => false]);

    provisionManagedPlatform();

    expect(MagicAIPlatform::where('is_managed', true)->sole()->is_default)->toBeTrue();

    $client = MagicAIPlatform::factory()->default()->create();

    provisionManagedPlatform('');

    expect(MagicAIPlatform::where('is_managed', true)->sole()->is_default)->toBeFalse()
        ->and($client->fresh()->is_default)->toBeTrue();
});

it('makes an existing managed platform the default again when none is set', function () {
    $platform = managedPlatformRecord();

    MagicAIPlatform::query()->update(['is_default' => false, 'status' => false]);

    provisionManagedPlatform('');

    expect($platform->fresh()->is_default)->toBeTrue()
        ->and($platform->fresh()->status)->toBeTrue();
});

it('refuses to provision without managed models', function () {
    config(['magic_ai.managed.models' => []]);

    artisan('unopim:magic-ai:managed-platform')->assertFailed();

    expect(MagicAIPlatform::where('is_managed', true)->exists())->toBeFalse();
});

it('refuses to provision without a key when no managed platform exists', function () {
    artisan('unopim:magic-ai:managed-platform', ['--no-interaction' => true])
        ->expectsOutputToContain(trans('admin::app.configuration.platform.message.managed-key-required'))
        ->assertFailed();

    artisan('unopim:magic-ai:managed-platform')
        ->expectsQuestion(trans('admin::app.configuration.platform.message.managed-key-prompt'), '')
        ->assertFailed();

    expect(MagicAIPlatform::where('is_managed', true)->exists())->toBeFalse();
});

it('refuses to delete the managed platform', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    deleteJson(route('admin.magic_ai.platform.delete', $platform->id))
        ->assertBadRequest()
        ->assertJson(['message' => trans('admin::app.configuration.platform.message.managed-cannot-delete')]);

    expect($platform->fresh())->not->toBeNull();
});

it('hides the delete action on the managed row only', function () {
    $this->loginWithPermissions('all');

    $managed = managedPlatformRecord();
    $client = MagicAIPlatform::factory()->create();

    $records = collect($this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.magic_ai.platform.index'))
        ->assertOk()
        ->json('records'))
        ->keyBy('id');

    expect(collect($records[$managed->id]['actions'])->pluck('index')->all())->not->toContain('delete')
        ->and(collect($records[$client->id]['actions'])->pluck('index')->all())->toContain('delete');
});

it('exposes the managed flag when editing', function () {
    $this->loginWithPermissions('all');

    getJson(route('admin.magic_ai.platform.edit', managedPlatformRecord()->id))
        ->assertOk()
        ->assertJsonPath('data.is_managed', true);
});

it('keeps the stored managed key on its provider, endpoint and extras', function (array $overrides) {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload($overrides))
        ->assertJsonValidationErrors('api_key');

    postJson(route('admin.magic_ai.platform.store'), managedPayload($overrides + ['id' => $platform->id]))
        ->assertJsonValidationErrors('api_key');

    expect($platform->fresh()->provider)->toBe(AiProvider::Concentrate->value)
        ->and($platform->fresh()->api_url)->toBeNull();
})->with([
    'another provider' => [['provider' => AiProvider::OpenAI->value]],
    'another endpoint' => [['api_url' => 'https://api.openai.com/v1']],
    'provider extras'  => [['extras' => json_encode(['organization' => 'org-1'])]],
    'an omitted key'   => [['api_key' => '', 'api_url' => 'https://api.openai.com/v1']],
]);

it('rejects a model outside the managed list on the stored managed key', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload(['models' => 'gpt-oss-120b,gpt-5.1']))
        ->assertJsonValidationErrors('models');

    expect($platform->fresh()->models)->toBe('gpt-oss-120b,gpt-oss-20b');
});

it('saves the managed platform within its models and endpoint', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload([
        'api_url' => AiProvider::Concentrate->defaultUrl().'/',
        'models'  => 'gpt-oss-20b',
    ]))->assertOk();

    expect($platform->fresh()->models)->toBe('gpt-oss-20b')
        ->and($platform->fresh()->is_managed)->toBeTrue();
});

it('never sends the stored managed key elsewhere on a connection test or model fetch', function (string $route) {
    $this->loginWithPermissions('all');

    postJson(route($route), [
        'id'       => managedPlatformRecord()->id,
        'provider' => AiProvider::Custom->value,
        'api_key'  => '********',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-oss-120b',
    ])->assertJsonValidationErrors('api_key');
})->with([
    'connection test' => ['admin.magic_ai.platform.test'],
    'model fetch'     => ['admin.magic_ai.platform.fetch_models'],
]);

it('lifts the restriction and the flag once the client enters their own key', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload([
        'provider' => AiProvider::OpenAI->value,
        'api_key'  => 'sk-client-own-key',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-5.1',
    ]))->assertOk();

    $platform->refresh();

    expect($platform->is_managed)->toBeFalse()
        ->and($platform->safeApiKey())->toBe('sk-client-own-key')
        ->and($platform->models)->toBe('gpt-5.1');

    deleteJson(route('admin.magic_ai.platform.delete', $platform->id))->assertOk();
});

it('offers only the managed models on a fetch without calling the provider', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.platform.fetch_models'), [
        'id'       => managedPlatformRecord()->id,
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

it('caps the pre-selected managed models at the auto-select limit', function () {
    config(['magic_ai.managed.models' => ['gpt-oss-120b', 'gpt-oss-20b', 'qwen3-32b', 'llama-4-scout', 'glm-4.6', 'kimi-k2-5', 'mistral-small-3.2']]);

    $this->loginWithPermissions('all');

    $response = postJson(route('admin.magic_ai.platform.fetch_models'), [
        'id'       => managedPlatformRecord()->id,
        'provider' => AiProvider::Concentrate->value,
        'api_key'  => '********',
    ])->assertOk();

    expect($response->json('models'))->toHaveCount(7)
        ->and($response->json('recommended'))->toHaveCount(ModelRecommender::AUTO_SELECT_LIMIT);
});

it('rejects an empty endpoint when the managed endpoint is not the provider default', function () {
    config(['magic_ai.managed.api_url' => 'https://api.openai.com/v1']);

    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload())
        ->assertJsonValidationErrors('api_key');

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload(['api_url' => 'https://api.openai.com/v1']))
        ->assertOk();
});

it('leaves client platforms unrestricted', function (AiProvider $provider) {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->provider($provider)->create(['api_key' => 'sk-client-own-key']);

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload([
        'provider' => $provider->value,
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-5.1,claude-opus-5',
    ]))->assertOk();

    expect($platform->fresh()->models)->toBe('gpt-5.1,claude-opus-5');
})->with([
    'Concentrate AI' => [AiProvider::Concentrate],
    'OpenAI'         => [AiProvider::OpenAI],
]);

it('keeps platforms saved before the flag existed unrestricted and deletable', function () {
    $this->loginWithPermissions('all');

    $id = DB::table('magic_ai_platforms')->insertGetId([
        'label'      => 'Legacy Concentrate',
        'provider'   => AiProvider::Concentrate->value,
        'api_key'    => Crypt::encryptString('sk-legacy-key'),
        'models'     => 'gpt-5.1',
        'is_default' => false,
        'status'     => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $platform = MagicAIPlatform::findOrFail($id);

    expect($platform->is_managed)->toBeFalse()
        ->and($platform->safeApiKey())->toBe('sk-legacy-key');

    putJson(route('admin.magic_ai.platform.update', $id), managedPayload([
        'api_url' => 'https://api.openai.com/v1',
        'models'  => 'gpt-5.1,claude-opus-5',
    ]))->assertOk();

    deleteJson(route('admin.magic_ai.platform.delete', $id))->assertOk();
});

it('swaps a model outside the managed list at generation time', function () {
    $managedPlatform = app(ManagedPlatform::class);

    $managed = managedPlatformRecord();

    expect($managedPlatform->resolveModel($managed, 'gpt-5.1'))->toBe('gpt-oss-120b')
        ->and($managedPlatform->resolveModel($managed, 'gpt-oss-20b'))->toBe('gpt-oss-20b');

    $clientPlatform = MagicAIPlatform::factory()->create(['provider' => AiProvider::Concentrate->value, 'api_key' => MANAGED_KEY]);

    expect($managedPlatform->resolveModel($clientPlatform, 'gpt-5.1'))->toBe('gpt-5.1');
});
