<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\FindSimilarProducts;
use Webkul\AiAgent\Chat\Tools\ListAttributes;
use Webkul\AiAgent\Chat\Tools\SearchProducts;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

/**
 * Deterministic ranking stand-in so similarity tests do not depend on a
 * configured embeddings provider: every candidate scores 1.0 in pool order.
 */
class FakeEmbeddingSimilarityService extends EmbeddingSimilarityService
{
    public function rank(string $query, array $documents, ?int $limit = null): array
    {
        $scores = array_map(
            fn (int $index) => ['index' => $index, 'score' => 1.0],
            array_keys($documents),
        );

        return is_null($limit) ? $scores : array_slice($scores, 0, max(1, $limit));
    }
}

it('scopes similar product candidates to the source product family by default', function () {
    $admin = $this->loginAsAdmin();

    app()->instance(EmbeddingSimilarityService::class, new FakeEmbeddingSimilarityService);

    $scope = createFamilyScopedFixtures();

    $result = decodeFamilyScopedToolResult(
        app(FindSimilarProducts::class)
            ->register(buildFamilyScopedChatContext($admin))
            ->handle(new Request(['sku' => $scope['source']->sku]))
    );

    $skus = array_column($result['products'], 'sku');

    expect($result['scoped_to_same_family'])->toBeTrue();
    expect($skus)->toContain($scope['sameFamily']->sku);
    expect($skus)->not->toContain($scope['otherFamily']->sku);
    expect($skus)->not->toContain($scope['source']->sku);
});

it('searches across all families when same_family_only is false', function () {
    $admin = $this->loginAsAdmin();

    app()->instance(EmbeddingSimilarityService::class, new FakeEmbeddingSimilarityService);

    $scope = createFamilyScopedFixtures();

    $result = decodeFamilyScopedToolResult(
        app(FindSimilarProducts::class)
            ->register(buildFamilyScopedChatContext($admin))
            ->handle(new Request(['sku' => $scope['source']->sku, 'same_family_only' => false]))
    );

    $skus = array_column($result['products'], 'sku');

    expect($result['scoped_to_same_family'])->toBeFalse();
    expect($skus)->toContain($scope['sameFamily']->sku);
    expect($skus)->toContain($scope['otherFamily']->sku);
    expect($skus)->not->toContain($scope['source']->sku);
});

it('uses the edit-page product context as the similarity source', function () {
    $admin = $this->loginAsAdmin();

    app()->instance(EmbeddingSimilarityService::class, new FakeEmbeddingSimilarityService);

    $scope = createFamilyScopedFixtures();

    $result = decodeFamilyScopedToolResult(
        app(FindSimilarProducts::class)
            ->register(buildFamilyScopedChatContext($admin, $scope['source']->id, $scope['source']->sku))
            ->handle(new Request([]))
    );

    $skus = array_column($result['products'], 'sku');

    expect($result['scoped_to_same_family'])->toBeTrue();
    expect($skus)->toContain($scope['sameFamily']->sku);
    expect($skus)->not->toContain($scope['otherFamily']->sku);
    expect($skus)->not->toContain($scope['source']->sku);
});

it('filters product search by family_code while staying global by default', function () {
    $admin = $this->loginAsAdmin();

    $suffix = random_int(10000, 99999);
    $needle = 'FamScopeNeedle'.$suffix;

    $familyA = AttributeFamily::factory()->create(['code' => 'search_fam_a_'.$suffix]);
    $familyB = AttributeFamily::factory()->create(['code' => 'search_fam_b_'.$suffix]);

    $inFamily = createFamilyScopedProduct('SRCH-FAM-A-'.$suffix, $familyA->id, $needle);
    $outOfFamily = createFamilyScopedProduct('SRCH-FAM-B-'.$suffix, $familyB->id, $needle);

    $tool = app(SearchProducts::class)->register(buildFamilyScopedChatContext($admin));

    $global = decodeFamilyScopedToolResult($tool->handle(new Request(['query' => $needle])));
    $globalSkus = array_column($global['products'], 'sku');

    expect($globalSkus)->toContain($inFamily->sku);
    expect($globalSkus)->toContain($outOfFamily->sku);

    $scoped = decodeFamilyScopedToolResult(
        $tool->handle(new Request(['query' => $needle, 'family_code' => $familyA->code]))
    );
    $scopedSkus = array_column($scoped['products'], 'sku');

    expect($scopedSkus)->toContain($inFamily->sku);
    expect($scopedSkus)->not->toContain($outOfFamily->sku);
    expect($scoped['scope']['family_code'])->toBe($familyA->code);
});

it('rejects malformed family_code and category_code values in product search', function () {
    $admin = $this->loginAsAdmin();

    $tool = app(SearchProducts::class)->register(buildFamilyScopedChatContext($admin));

    $badFamily = decodeFamilyScopedToolResult(
        $tool->handle(new Request(['family_code' => 'bad"code']))
    );
    $badCategory = decodeFamilyScopedToolResult(
        $tool->handle(new Request(['category_code' => "bad'code"]))
    );

    expect($badFamily)->toHaveKey('error');
    expect($badCategory)->toHaveKey('error');
});

it('lists attributes for an explicit family_code override', function () {
    $admin = $this->loginAsAdmin();

    $suffix = random_int(10000, 99999);
    $family = AttributeFamily::factory()->create(['code' => 'list_attr_fam_'.$suffix]);

    $result = decodeFamilyScopedToolResult(
        app(ListAttributes::class)
            ->register(buildFamilyScopedChatContext($admin))
            ->handle(new Request(['family_code' => $family->code]))
    );

    expect($result['family_id'])->toBe($family->id);
    expect($result['family_code'])->toBe($family->code);

    $unknown = decodeFamilyScopedToolResult(
        app(ListAttributes::class)
            ->register(buildFamilyScopedChatContext($admin))
            ->handle(new Request(['family_code' => 'missing_fam_'.$suffix]))
    );

    expect($unknown)->toHaveKey('error');
});

it('defaults list_attributes to the family of the product being edited', function () {
    $admin = $this->loginAsAdmin();

    $suffix = random_int(10000, 99999);
    $family = AttributeFamily::factory()->create(['code' => 'ctx_attr_fam_'.$suffix]);

    $product = Product::factory()->simple()->withInitialValues()->create([
        'sku'                 => 'CTX-ATTR-'.$suffix,
        'attribute_family_id' => $family->id,
    ]);

    $result = decodeFamilyScopedToolResult(
        app(ListAttributes::class)
            ->register(buildFamilyScopedChatContext($admin, $product->id, $product->sku))
            ->handle(new Request([]))
    );

    expect($result['family_id'])->toBe($family->id);
    expect($result['family_code'])->toBe($family->code);
});

/**
 * Two fresh families: source + sibling in family A, one product in family B.
 *
 * @return array{source: Product, sameFamily: Product, otherFamily: Product}
 */
function createFamilyScopedFixtures(): array
{
    $suffix = random_int(10000, 99999);

    $familyA = AttributeFamily::factory()->create(['code' => 'sim_fam_a_'.$suffix]);
    $familyB = AttributeFamily::factory()->create(['code' => 'sim_fam_b_'.$suffix]);

    return [
        'source'      => createFamilyScopedProduct('SIM-SRC-'.$suffix, $familyA->id, 'Scoped source '.$suffix),
        'sameFamily'  => createFamilyScopedProduct('SIM-SAME-'.$suffix, $familyA->id, 'Scoped sibling '.$suffix),
        'otherFamily' => createFamilyScopedProduct('SIM-OTHER-'.$suffix, $familyB->id, 'Scoped outsider '.$suffix),
    ];
}

function createFamilyScopedProduct(string $sku, int $familyId, string $name): Product
{
    $product = Product::factory()->simple()->withInitialValues()->create([
        'sku'                 => $sku,
        'attribute_family_id' => $familyId,
    ]);

    $values = $product->values;
    $values['channel_locale_specific']['default']['en_US']['name'] = $name;
    $product->values = $values;
    $product->save();

    return $product;
}

function buildFamilyScopedChatContext($admin, ?int $productId = null, ?string $productSku = null): ChatContext
{
    return new ChatContext(
        message: 'Scoped retrieval',
        history: [],
        productId: $productId,
        productSku: $productSku,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform([
            'provider' => 'openai',
            'models'   => 'gpt-4o',
        ]),
        model: 'gpt-4o',
        user: $admin,
    );
}

function decodeFamilyScopedToolResult(string $result): array
{
    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
}
