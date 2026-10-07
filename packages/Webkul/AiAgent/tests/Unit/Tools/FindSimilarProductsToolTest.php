<?php

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\FindSimilarProducts;
use Webkul\AiAgent\Jobs\IndexProductEmbeddingsJob;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

function similarChatContext(MagicAIPlatform $platform): ChatContext
{
    return new ChatContext(
        message: 'Find similar products',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: $platform,
        model: 'test-model',
        user: Admin::query()->firstOrFail(),
    );
}

function similarPlatform(string $provider, array $attributes = []): MagicAIPlatform
{
    return MagicAIPlatform::query()->create(array_merge([
        'label'      => ucfirst($provider),
        'provider'   => $provider,
        'api_url'    => null,
        'api_key'    => 'test-key',
        'models'     => 'model-a',
        'status'     => 1,
        'is_default' => 0,
    ], $attributes));
}

function runFindSimilar(MagicAIPlatform $platform, array $arguments): array
{
    $tool = resolve(FindSimilarProducts::class)->register(similarChatContext($platform));

    return json_decode((string) $tool->handle(new Request($arguments)), true);
}

beforeEach(function () {
    MagicAIPlatform::query()->delete();
});

it('flags only providers with an embeddings api as embedding capable', function (AiProvider $provider) {
    $embeddingProviders = [AiProvider::OpenAI, AiProvider::Gemini, AiProvider::Mistral, AiProvider::Ollama, AiProvider::Azure, AiProvider::OpenRouter];

    expect($provider->supportsEmbeddings())->toBe(in_array($provider, $embeddingProviders, true));
})->with(AiProvider::cases());

it('prefers the chat platform for embeddings when it supports them', function () {
    similarPlatform('openai', ['is_default' => 1]);
    $chat = similarPlatform('gemini');

    expect(resolve(EmbeddingSimilarityService::class)->resolvePlatform($chat)->id)->toBe($chat->id);
});

it('falls back to an active embedding capable platform when the chat platform has none', function () {
    $chat = similarPlatform('anthropic', ['is_default' => 1]);
    similarPlatform('openai', ['status' => 0]);
    $openAi = similarPlatform('openai');

    expect(resolve(EmbeddingSimilarityService::class)->resolvePlatform($chat)->id)->toBe($openAi->id);
});

it('resolves no platform when none can produce embeddings', function () {
    $chat = similarPlatform('anthropic');
    similarPlatform('groq');

    expect(resolve(EmbeddingSimilarityService::class)->resolvePlatform($chat))->toBeNull();
});

it('only reports a fixed vector size for providers that ignore the requested one', function () {
    expect(AiProvider::Mistral->fixedEmbeddingDimensions())->toBe(1024)
        ->and(AiProvider::OpenAI->fixedEmbeddingDimensions())->toBeNull()
        ->and(AiProvider::Gemini->fixedEmbeddingDimensions())->toBeNull()
        ->and(AiProvider::Ollama->fixedEmbeddingDimensions())->toBeNull();
});

it('skips a fixed size provider whose vectors do not fit the index', function () {
    $chat = similarPlatform('mistral', ['is_default' => 1]);
    $openAi = similarPlatform('openai');

    $service = resolve(EmbeddingSimilarityService::class);

    expect($service->resolvePlatform($chat, 1536)->id)->toBe($openAi->id)
        ->and($service->resolvePlatform($chat, 1024)->id)->toBe($chat->id)
        ->and($service->resolvePlatform($chat)->id)->toBe($chat->id);
});

it('resolves no platform when the only embedding provider cannot match the index size', function () {
    $chat = similarPlatform('mistral');

    expect(resolve(EmbeddingSimilarityService::class)->resolvePlatform($chat, 1536))->toBeNull();
});

it('only embeds through azure when an embedding deployment is configured', function () {
    $withoutDeployment = similarPlatform('azure', ['extras' => ['deployment' => 'gpt-4o']]);
    $withDeployment = similarPlatform('azure', ['extras' => ['deployment' => 'gpt-4o', 'embedding_deployment' => 'embed-large']]);

    $service = resolve(EmbeddingSimilarityService::class);

    expect($service->resolvePlatform($withoutDeployment)->id)->toBe($withDeployment->id);

    $withDeployment->update(['status' => 0]);

    expect($service->resolvePlatform($withoutDeployment))->toBeNull();
});

it('generates embeddings through the resolved platform provider', function () {
    Embeddings::fake();

    $chat = similarPlatform('anthropic');
    similarPlatform('gemini');

    $service = resolve(EmbeddingSimilarityService::class);

    $ranked = $service->rankOrFail('lumen pendant', ['lumen pendant black', 'cable tray'], 2, $service->resolvePlatform($chat));

    expect($ranked)->toHaveCount(2);

    Embeddings::assertGenerated(fn ($prompt): bool => $prompt->provider->name() === 'gemini');
});

it('keeps rank() returning no scores and logging when the provider rejects the call', function () {
    Embeddings::fake(fn () => throw new RuntimeException('Incorrect API key provided.'));
    Log::spy();

    similarPlatform('openai');

    expect(resolve(EmbeddingSimilarityService::class)->rank('lumen pendant', ['lumen pendant black']))->toBe([]);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context['error'] === 'Incorrect API key provided.');
});

it('suggests close skus when the requested sku does not exist', function () {
    Product::factory()->simple()->create(['sku' => 'qorvel-lumen-pendant']);
    Product::factory()->simple()->create(['sku' => 'qorvel-cable-tray']);

    $result = runFindSimilar(similarPlatform('anthropic'), ['sku' => 'qorvel-lumen-pendt']);

    expect($result['error'])->toContain('qorvel-lumen-pendt')
        ->and($result['did_you_mean'][0])->toBe('qorvel-lumen-pendant');
});

it('suggests close skus regardless of letter case', function () {
    Product::factory()->simple()->create(['sku' => 'qorvel-lumen-pendant']);

    $result = runFindSimilar(similarPlatform('anthropic'), ['sku' => 'QORVEL-LUMEN-PENDT']);

    expect($result['did_you_mean'])->toBe(['qorvel-lumen-pendant']);
});

it('ranks by keyword overlap and explains why when no platform can produce embeddings', function () {
    Embeddings::fake();

    $source = Product::factory()->simple()->create(['sku' => 'lumen-pendant']);
    $family = $source->attribute_family_id;

    Product::factory()->simple()->create(['sku' => 'lumen-pendant-black', 'attribute_family_id' => $family]);
    Product::factory()->simple()->create(['sku' => 'cable-tray', 'attribute_family_id' => $family]);

    $result = runFindSimilar(similarPlatform('anthropic', ['label' => 'Claude']), ['sku' => 'lumen-pendant']);

    expect($result['ranking'])->toBe('keyword')
        ->and($result['products'][0]['sku'])->toBe('lumen-pendant-black')
        ->and($result['note'])->toContain('Claude')
        ->and($result['note'])->toContain('embeddings')
        ->and($result['note'])->not->toContain('not currently set up');

    Embeddings::assertNothingGenerated();
});

it('names the failing platform and the provider reason when embeddings are rejected', function () {
    Embeddings::fake(fn () => throw new RuntimeException('Incorrect API key provided.'));

    $source = Product::factory()->simple()->create(['sku' => 'lumen-pendant']);

    Product::factory()->simple()->create(['sku' => 'lumen-pendant-black', 'attribute_family_id' => $source->attribute_family_id]);

    $result = runFindSimilar(similarPlatform('openai', ['label' => 'Team OpenAI']), ['sku' => 'lumen-pendant']);

    expect($result['ranking'])->toBe('keyword')
        ->and($result['products'][0]['sku'])->toBe('lumen-pendant-black')
        ->and($result['note'])->toContain('Team OpenAI')
        ->and($result['note'])->toContain('Incorrect API key provided.');
});

it('reports semantic ranking and the platform used when embeddings succeed', function () {
    Embeddings::fake();

    $source = Product::factory()->simple()->create(['sku' => 'lumen-pendant']);

    Product::factory()->simple()->create(['sku' => 'lumen-pendant-black', 'attribute_family_id' => $source->attribute_family_id]);

    $result = runFindSimilar(similarPlatform('openai', ['label' => 'Team OpenAI']), ['sku' => 'lumen-pendant']);

    expect($result['ranking'])->toBe('semantic')
        ->and($result['embedding_platform'])->toBe('Team OpenAI')
        ->and($result)->not->toHaveKey('note');
});

it('indexes product embeddings through the resolved platform at the index dimensions', function () {
    Embeddings::fake();

    similarPlatform('anthropic', ['is_default' => 1]);
    similarPlatform('gemini');

    $product = Product::factory()->simple()->create(['sku' => 'lumen-pendant-index']);

    $index = Mockery::mock(ProductEmbeddingIndex::class);
    $index->shouldReceive('isEnabled')->andReturn(true);
    $index->shouldReceive('existingContentHashes')->andReturn([]);
    $index->shouldReceive('ensureIndex')->once();
    $index->shouldReceive('dimensions')->andReturn(768);
    $index->shouldReceive('bulkUpsert')
        ->once()
        ->withArgs(fn (array $documents): bool => $documents[0]['product_id'] === $product->id)
        ->andReturn([]);

    (new IndexProductEmbeddingsJob([$product->id]))->handle($index, new ProductEmbeddingDocumentBuilder);

    Embeddings::assertGenerated(fn ($prompt): bool => $prompt->provider->name() === 'gemini' && $prompt->dimensions === 768);
});

it('indexes through the default embeddings provider at the index dimensions when no platform can embed', function () {
    Embeddings::fake();

    similarPlatform('anthropic', ['is_default' => 1]);

    $product = Product::factory()->simple()->create(['sku' => 'lumen-pendant-default']);

    $index = Mockery::mock(ProductEmbeddingIndex::class);
    $index->shouldReceive('isEnabled')->andReturn(true);
    $index->shouldReceive('existingContentHashes')->andReturn([]);
    $index->shouldReceive('ensureIndex')->once();
    $index->shouldReceive('dimensions')->andReturn(768);
    $index->shouldReceive('bulkUpsert')->once()->andReturn([]);

    (new IndexProductEmbeddingsJob([$product->id]))->handle($index, new ProductEmbeddingDocumentBuilder);

    Embeddings::assertGenerated(fn ($prompt): bool => $prompt->provider->name() !== 'anthropic' && $prompt->dimensions === 768);
});
