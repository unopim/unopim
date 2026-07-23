<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\CategoryTree;
use Webkul\AiAgent\Chat\Tools\EstimateTokens;
use Webkul\AiAgent\Chat\Tools\FindSimilarProducts;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\Category\Models\Category;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

/**
 * Deterministic embedding stand-in: rankProducts() returns preset kNN hits,
 * rank() scores documents by needle match so pruning order is predictable.
 */
class WiringFakeEmbeddingService extends EmbeddingSimilarityService
{
    /** @var array<int, array{product_id: int, score: float}> */
    public array $knnHits = [];

    public string $needle = '';

    public function rankProducts(string $query, ?int $limit = null, ?int $attributeFamilyId = null): array
    {
        return array_slice($this->knnHits, 0, $limit ?? 10);
    }

    public function rank(string $query, array $documents, ?int $limit = null): array
    {
        $scores = [];

        foreach ($documents as $index => $document) {
            $scores[] = [
                'index' => $index,
                'score' => str_contains($document, $this->needle) ? 1.0 : 0.1,
            ];
        }

        usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scores, 0, $limit ?? count($scores));
    }
}

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
