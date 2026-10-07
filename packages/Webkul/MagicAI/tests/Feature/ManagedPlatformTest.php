<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\ManagedPlatform;
use Webkul\MagicAI\Support\ModelRecommender;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

const MANAGED_KEY = 'sk-managed-account';

function managedPayload(array $overrides = []): array
{
    return array_merge([
        'label'    => 'Concentrate AI',
        'provider' => AiProvider::Concentrate->value,
        'api_key'  => '********',
        'models'   => 'gpt-oss-120b,gpt-oss-20b',
        'status'   => 1,
    ], $overrides);
}

function managedPlatformRecord(array $attributes = []): MagicAIPlatform
{
    return MagicAIPlatform::factory()->managed()->create(array_merge([
        'label'    => 'Concentrate AI',
        'provider' => AiProvider::Concentrate->value,
        'api_url'  => null,
        'api_key'  => MANAGED_KEY,
        'models'   => 'gpt-oss-120b,gpt-oss-20b',
    ], $attributes));
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

it('lets the admin change only the status and default of a managed platform', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload([
        'api_url'    => AiProvider::Concentrate->defaultUrl().'/',
        'is_default' => 1,
    ]))->assertOk();

    expect($platform->fresh()->is_default)->toBeTrue();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload(['status' => 0]))->assertOk();

    expect($platform->fresh()->status)->toBeFalse()
        ->and($platform->fresh()->is_managed)->toBeTrue();
});

it('makes a managed platform the default through the default route', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    postJson(route('admin.magic_ai.platform.set_default', $platform->id))->assertOk();

    expect($platform->fresh()->is_default)->toBeTrue();
});

it('keeps every other field of a managed platform for the command line', function (array $overrides) {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord();

    putJson(route('admin.magic_ai.platform.update', $platform->id), managedPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('platform')
        ->assertJsonPath('errors.platform.0', trans('admin::app.configuration.platform.message.managed-cli-only'));

    $platform->refresh();

    expect($platform->label)->toBe('Concentrate AI')
        ->and($platform->models)->toBe('gpt-oss-120b,gpt-oss-20b')
        ->and($platform->safeApiKey())->toBe(MANAGED_KEY)
        ->and($platform->is_managed)->toBeTrue();
})->with([
    'label'            => [['label' => 'Renamed']],
    'fewer models'     => [['models' => 'gpt-oss-20b']],
    'a new key'        => [['api_key' => 'sk-client-own-key']],
    'a new connection' => [[
        'provider' => AiProvider::OpenAI->value,
        'api_key'  => 'sk-client-own-key',
        'api_url'  => 'https://api.openai.com/v1',
        'models'   => 'gpt-5.1',
    ]],
]);

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
    $this->loginWithPermissions('all');

    $response = postJson(route('admin.magic_ai.platform.fetch_models'), [
        'id'       => managedPlatformRecord(['models' => 'gpt-oss-120b,gpt-oss-20b,qwen3-32b,llama-4-scout,glm-4.6,kimi-k2-5,mistral-small-3.2'])->id,
        'provider' => AiProvider::Concentrate->value,
        'api_key'  => '********',
    ])->assertOk();

    expect($response->json('models'))->toHaveCount(7)
        ->and($response->json('recommended'))->toHaveCount(ModelRecommender::AUTO_SELECT_LIMIT);
});

it('rejects an empty endpoint when the managed endpoint is not the provider default', function () {
    $this->loginWithPermissions('all');

    $platform = managedPlatformRecord(['api_url' => 'https://api.openai.com/v1']);

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

it('locks each managed platform to its own saved models', function () {
    $this->loginWithPermissions('all');

    $first = managedPlatformRecord();
    $second = managedPlatformRecord(['label' => 'Second', 'models' => 'qwen3-32b']);

    putJson(route('admin.magic_ai.platform.update', $second->id), managedPayload(['models' => 'gpt-oss-120b']))
        ->assertJsonValidationErrors('models');

    putJson(route('admin.magic_ai.platform.update', $first->id), managedPayload(['models' => 'qwen3-32b']))
        ->assertJsonValidationErrors('models');

    expect(app(ManagedPlatform::class)->resolveModel($second, 'gpt-oss-120b'))->toBe('qwen3-32b');
});

it('keeps the stored key when the literal key 0 is submitted', function () {
    $this->loginWithPermissions('all');

    $platform = MagicAIPlatform::factory()->create(['api_key' => 'sk-original']);

    putJson(route('admin.magic_ai.platform.update', $platform->id), [
        'label'    => $platform->label,
        'provider' => $platform->provider,
        'api_url'  => $platform->api_url,
        'api_key'  => '0',
        'models'   => $platform->models,
        'status'   => 1,
    ])->assertOk();

    expect($platform->fresh()->api_key)->toBe('sk-original');
});

it('answers a malformed payload with 422 instead of a server error', function (string $route, array $payload) {
    $this->loginWithPermissions('all');

    postJson(route($route), $payload)->assertUnprocessable();
})->with([
    'fetch with array values'  => ['admin.magic_ai.platform.fetch_models', ['provider' => ['x'], 'api_url' => ['x']]],
    'test with array provider' => ['admin.magic_ai.platform.test', ['provider' => ['x'], 'models' => 'gpt-4o']],
    'store with array models'  => ['admin.magic_ai.platform.store', ['label' => 'x', 'provider' => 'openai', 'models' => ['a']]],
]);
