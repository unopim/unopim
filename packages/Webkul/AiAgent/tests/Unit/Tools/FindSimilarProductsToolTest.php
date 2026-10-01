<?php

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

it('flags only providers with an embeddings api as embedding capable', function () {
    expect(AiProvider::OpenAI->supportsEmbeddings())->toBeTrue()
        ->and(AiProvider::Gemini->supportsEmbeddings())->toBeTrue()
        ->and(AiProvider::Mistral->supportsEmbeddings())->toBeTrue()
        ->and(AiProvider::Ollama->supportsEmbeddings())->toBeTrue()
        ->and(AiProvider::Anthropic->supportsEmbeddings())->toBeFalse()
        ->and(AiProvider::Groq->supportsEmbeddings())->toBeFalse()
        ->and(AiProvider::DeepSeek->supportsEmbeddings())->toBeFalse();
});

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

it('generates embeddings through the resolved platform provider', function () {
    Embeddings::fake();

    $chat = similarPlatform('anthropic');
    similarPlatform('gemini');

    $service = resolve(EmbeddingSimilarityService::class);

    $ranked = $service->rankOrFail('lumen pendant', ['lumen pendant black', 'cable tray'], 2, $service->resolvePlatform($chat));

    expect($ranked)->toHaveCount(2);

    Embeddings::assertGenerated(fn ($prompt): bool => $prompt->provider->name() === 'gemini');
});

it('suggests close skus when the requested sku does not exist', function () {
    Product::factory()->simple()->create(['sku' => 'halden-lumen-pendant']);
    Product::factory()->simple()->create(['sku' => 'halden-cable-tray']);

    $result = runFindSimilar(similarPlatform('anthropic'), ['sku' => 'halden-lumen-pendt']);

    expect($result['error'])->toContain('halden-lumen-pendt')
        ->and($result['did_you_mean'][0])->toBe('halden-lumen-pendant');
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
