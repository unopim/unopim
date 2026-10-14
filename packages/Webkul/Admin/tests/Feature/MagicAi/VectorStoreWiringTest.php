<?php

use Laravel\Ai\Embeddings;
use Laravel\Ai\Tools\Request;
use Webkul\Admin\Tests\Support\InMemoryEmbeddingElasticSearch;
use Webkul\Admin\Tests\Support\WiringFakeEmbeddingService;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\CategoryTree;
use Webkul\AiAgent\Chat\Tools\EstimateTokens;
use Webkul\AiAgent\Chat\Tools\FindSimilarProducts;
use Webkul\AiAgent\Jobs\IndexProductEmbeddingsJob;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\Category\Models\Category;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

function buildWiringChatContext($admin): ChatContext
{
    return new ChatContext(
        message: 'test',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        user: $admin,
    );
}

it('serves find_similar_products from the vector store when kNN returns hits', function () {
    $admin = $this->loginAsAdmin();

    $target = Product::factory()->simple()->withInitialValues()->create([
        'sku' => 'KNN-HIT-'.random_int(10000, 99999),
    ]);

    $fake = new WiringFakeEmbeddingService;
    $fake->knnHits = [['product_id' => $target->id, 'score' => 0.93]];
    app()->instance(EmbeddingSimilarityService::class, $fake);

    $result = json_decode(
        app(FindSimilarProducts::class)
            ->register(buildWiringChatContext($admin))
            ->handle(new Request(['query' => 'red sneakers'])),
        true,
    );

    expect($result['source'] ?? null)->toBe('vector_store');
    expect(array_column($result['products'], 'sku'))->toContain($target->sku);
    expect($result['products'][0]['similarity_score'])->toBe(0.93);
});

it('falls back to in-memory ranking when the vector store returns nothing', function () {
    $admin = $this->loginAsAdmin();

    Product::factory()->simple()->withInitialValues()->create([
        'sku' => 'KNN-FALLBACK-'.random_int(10000, 99999),
    ]);

    $fake = new WiringFakeEmbeddingService;
    $fake->knnHits = [];
    $fake->needle = 'KNN-FALLBACK';
    app()->instance(EmbeddingSimilarityService::class, $fake);

    $result = json_decode(
        app(FindSimilarProducts::class)
            ->register(buildWiringChatContext($admin))
            ->handle(new Request(['query' => 'KNN-FALLBACK sneakers'])),
        true,
    );

    expect($result['source'] ?? null)->toBeNull();
    expect($result['total'])->toBeGreaterThan(0);
});

it('finds same-family products through the vector store by default for a sku lookup', function () {
    $admin = $this->loginAsAdmin();

    $source = Product::factory()->simple()->withInitialValues()->create();
    $sibling = Product::factory()->simple()->withInitialValues()->create([
        'attribute_family_id' => $source->attribute_family_id,
    ]);

    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => true]);

    $es = (new InMemoryEmbeddingElasticSearch)->install();

    Embeddings::fake([[array_fill(0, 8, 0.5)], [array_fill(0, 8, 0.5)]]);

    (new IndexProductEmbeddingsJob([$sibling->id]))->handle(new ProductEmbeddingIndex, new ProductEmbeddingDocumentBuilder);

    $result = json_decode(
        app(FindSimilarProducts::class)
            ->register(buildWiringChatContext($admin))
            ->handle(new Request(['sku' => $source->sku])),
        true,
    );

    expect(end($es->searchBodies)['knn']['filter'])->toBe(['term' => ['attribute_family_id' => (int) $source->attribute_family_id]])
        ->and($result['source'] ?? null)->toBe('vector_store')
        ->and(array_column($result['products'], 'sku'))->toContain($sibling->sku);
});

it('prunes category branches by relevance when a relevance_query is given', function () {
    $admin = $this->loginAsAdmin();

    $suffix = random_int(10000, 99999);

    $root = Category::create(['code' => 'prune_root_'.$suffix]);
    $shoes = Category::create(['code' => 'prune_shoes_'.$suffix, 'parent_id' => $root->id]);
    Category::create(['code' => 'prune_pants_'.$suffix, 'parent_id' => $root->id]);
    Category::create(['code' => 'prune_hats_'.$suffix, 'parent_id' => $root->id]);

    $fake = new WiringFakeEmbeddingService;
    $fake->needle = 'prune_shoes_'.$suffix;
    app()->instance(EmbeddingSimilarityService::class, $fake);

    $result = json_decode(
        app(CategoryTree::class)
            ->register(buildWiringChatContext($admin))
            ->handle(new Request([
                'parent_code'        => $root->code,
                'children_per_level' => 1,
                'depth'              => 1,
                'relevance_query'    => 'running shoes',
            ])),
        true,
    );

    $codes = array_column($result['categories'] ?? [], 'code');
    expect($codes)->toContain($shoes->code);
    expect($codes)->toHaveCount(1);
});

it('estimates bulk operation tokens from a product sample', function () {
    $admin = $this->loginAsAdmin();

    Product::factory()->simple()->withInitialValues()->create([
        'sku' => 'EST-'.random_int(10000, 99999),
    ]);

    $result = json_decode(
        app(EstimateTokens::class)
            ->register(buildWiringChatContext($admin))
            ->handle(new Request(['filter_by' => 'all', 'limit' => 25])),
        true,
    );

    expect($result['products'])->toBeGreaterThan(0);
    expect($result['estimated_input_tokens'])->toBeGreaterThan(0);
    expect($result['estimated_total_tokens'])
        ->toBe($result['estimated_input_tokens'] + $result['estimated_output_tokens']);
});
