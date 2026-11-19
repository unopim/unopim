<?php

use Illuminate\Support\Facades\DB;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\AIModel;

it('encrypts the api key at rest and hides it from array output', function () {
    $platform = MagicAIPlatform::factory()->create(['api_key' => 'sk-secret-key']);

    expect(DB::table('magic_ai_platforms')->where('id', $platform->id)->value('api_key'))
        ->not->toBe('sk-secret-key')
        ->and($platform->toArray())->not->toHaveKey('api_key');
});

it('returns null instead of throwing when the stored key cannot be decrypted', function () {
    $platform = MagicAIPlatform::factory()->create();

    DB::table('magic_ai_platforms')->where('id', $platform->id)->update(['api_key' => 'not-encrypted']);

    $platform = $platform->fresh();

    expect($platform->safeApiKey())->toBeNull()
        ->and($platform->apiKeyError())->toBeString();
});

it('splits the stored model list into trimmed entries', function () {
    $platform = MagicAIPlatform::factory()->create(['models' => 'gpt-4o-mini, gpt-4o ,dall-e-3']);

    expect($platform->model_list)->toBe(['gpt-4o-mini', 'gpt-4o', 'dall-e-3']);
});

it('keeps exactly one default platform', function () {
    $first = MagicAIPlatform::factory()->default()->create();
    $second = MagicAIPlatform::factory()->default()->create();

    expect($second->fresh()->is_default)->toBeTrue()
        ->and($first->fresh()->is_default)->toBeFalse()
        ->and(MagicAIPlatform::where('is_default', true)->count())->toBe(1);
});

it('falls back to any enabled platform when the default one is disabled', function () {
    MagicAIPlatform::factory()->create(['is_default' => true, 'status' => false]);
    $enabled = MagicAIPlatform::factory()->create(['label' => 'Fallback']);

    expect(resolve(MagicAIPlatformRepository::class)->getActiveDefault()->id)->toBe($enabled->id);
});

it('lists only enabled platforms as selectable options', function () {
    MagicAIPlatform::factory()->create(['label' => 'Enabled']);
    MagicAIPlatform::factory()->disabled()->create(['label' => 'Disabled']);

    $labels = array_column(resolve(MagicAIPlatformRepository::class)->getActivePlatformOptions(), 'label');

    expect(implode('|', $labels))->toContain('Enabled')->not->toContain('Disabled');
});

it('offers no models when the install has no platform', function () {
    expect(AIModel::getModels())->toBe([])
        ->and(AIModel::validate())->toBe([])
        ->and(AIModel::getAvailableModels())->toBe([]);
});

it('offers the default platform models as id/label pairs', function () {
    MagicAIPlatform::factory()->default()->create(['models' => 'gpt-4o-mini']);

    expect(AIModel::getModels())->toBe([['id' => 'gpt-4o-mini', 'label' => 'gpt-4o-mini']]);
});

it('keeps only image capable models for an image capable provider', function () {
    $platform = MagicAIPlatform::factory()->provider(AiProvider::OpenAI)->create();

    expect(AIModel::filterImageModels(['gpt-4o', 'dall-e-3', 'gpt-image-1'], $platform->id))
        ->toBe(['dall-e-3', 'gpt-image-1']);
});

it('offers no image models at all for a provider without an image endpoint', function () {
    $platform = MagicAIPlatform::factory()->provider(AiProvider::Anthropic)->create();

    expect(AIModel::filterImageModels(['claude-sonnet-4-5'], $platform->id))->toBe([]);
});

it('offers no image models when an image capable provider exposes only chat models', function (AiProvider $provider, array $models): void {
    $platform = MagicAIPlatform::factory()->provider($provider)->create();

    expect(AIModel::filterImageModels($models, $platform->id))->toBe([]);
})->with([
    'openai chat models' => [AiProvider::OpenAI, ['gpt-4o', 'gpt-5-mini']],
    'xai chat model'     => [AiProvider::XAI, ['grok-4']],
]);

it('offers no image models for an unknown platform id', function (): void {
    expect(AIModel::filterImageModels(['gpt-4o'], 999999))->toBe([]);
});

it('offers no image models when no platform id is given and no platform is active', function (): void {
    MagicAIPlatform::query()->delete();

    expect(AIModel::filterImageModels(['gpt-4o', 'dall-e-3']))->toBe([]);
});

it('keeps prefixed and chatgpt image models for an openai platform', function (): void {
    $platform = MagicAIPlatform::factory()->provider(AiProvider::OpenAI)->create();

    expect(AIModel::filterImageModels(['gpt-4o', 'chatgpt-image-latest', 'openai/gpt-image-1'], $platform->id))
        ->toBe(['chatgpt-image-latest', 'openai/gpt-image-1']);
});

it('keeps the full model list for an image capable platform on a custom api url when no name matches', function (): void {
    $platform = MagicAIPlatform::factory()->provider(AiProvider::OpenAI)->create(['api_url' => 'https://gateway.example.test/v1']);

    expect(AIModel::filterImageModels(['gpt-4o', 'house-renderer'], $platform->id))
        ->toBe(['gpt-4o', 'house-renderer']);
});

it('filters strictly for an image capable platform on its stock api url', function (?string $apiUrl): void {
    $platform = MagicAIPlatform::factory()->provider(AiProvider::OpenAI)->create(['api_url' => $apiUrl]);

    expect(AIModel::filterImageModels(['gpt-4o', 'house-renderer'], $platform->id))->toBe([]);
})->with([
    'no api url'          => [null],
    'stock api url'       => ['https://api.openai.com/v1'],
    'stock with trailing' => ['https://api.openai.com/v1/'],
]);

it('still filters by name for a platform on a custom api url when image models match', function (): void {
    $platform = MagicAIPlatform::factory()->provider(AiProvider::OpenAI)->create(['api_url' => 'https://gateway.example.test/v1']);

    expect(AIModel::filterImageModels(['gpt-4o', 'dall-e-3'], $platform->id))->toBe(['dall-e-3']);
});
