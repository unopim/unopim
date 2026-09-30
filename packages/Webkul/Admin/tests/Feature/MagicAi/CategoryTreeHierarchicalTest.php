<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\CategoryTree;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

it('respects children_per_level and reports totals on the root listing', function () {
    $admin = $this->loginAsAdmin();

    $result = invokeCategoryTreeTool($admin, ['children_per_level' => 1, 'depth' => 1]);

    expect($result['parent'])->toBeNull();
    expect($result['categories'])->toBeArray();
    expect(count($result['categories']))->toBeLessThanOrEqual(1);
    expect($result['total_at_level'])->toBeGreaterThanOrEqual(1);
    expect($result['has_more_at_level'])->toBe($result['total_at_level'] > count($result['categories']));

    foreach ($result['categories'] as $node) {
        expect($node)->toHaveKeys(['id', 'code', 'name', 'parent_id', 'total_children', 'has_more', 'children']);
    }
});

it('drills into a branch by parent_code, limits children per level and flags has_more', function () {
    $admin = $this->loginAsAdmin();

    $fixture = createCategoryTreeFixture();

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['parent']->code,
        'depth'              => 1,
        'children_per_level' => 3,
    ]);

    expect($result['parent']['code'])->toBe($fixture['parent']->code);
    expect($result['total_at_level'])->toBe(5);
    expect($result['has_more_at_level'])->toBeTrue();
    expect($result['categories'])->toHaveCount(3);

    $childCodes = array_map(fn ($child) => $child->code, $fixture['children']);
    $returnedCodes = array_column($result['categories'], 'code');

    expect(array_diff($returnedCodes, $childCodes))->toBeEmpty();

    $firstChild = collect($result['categories'])->firstWhere('code', $fixture['children'][0]->code);

    expect($firstChild)->not->toBeNull();
    expect($firstChild['total_children'])->toBe(2);
    expect($firstChild['children'])->toBeEmpty();
    expect($firstChild['has_more'])->toBeTrue();
    expect($firstChild['name'])->toBe('Child One '.$fixture['suffix']);
});

it('expands grandchildren when depth allows and clears has_more on fully expanded nodes', function () {
    $admin = $this->loginAsAdmin();

    $fixture = createCategoryTreeFixture();

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['parent']->code,
        'depth'              => 2,
        'children_per_level' => 10,
    ]);

    expect($result['categories'])->toHaveCount(5);
    expect($result['has_more_at_level'])->toBeFalse();

    $firstChild = collect($result['categories'])->firstWhere('code', $fixture['children'][0]->code);

    expect($firstChild['total_children'])->toBe(2);
    expect($firstChild['children'])->toHaveCount(2);
    expect($firstChild['has_more'])->toBeFalse();

    $grandchildCodes = array_map(fn ($grandchild) => $grandchild->code, $fixture['grandchildren']);

    expect(array_column($firstChild['children'], 'code'))->toEqualCanonicalizing($grandchildCodes);

    $leafChild = collect($result['categories'])->firstWhere('code', $fixture['children'][1]->code);

    expect($leafChild['total_children'])->toBe(0);
    expect($leafChild['has_more'])->toBeFalse();
    expect($leafChild['children'])->toBeEmpty();
});

it('returns only the requested branch when drilling down', function () {
    $admin = $this->loginAsAdmin();

    $fixture = createCategoryTreeFixture();

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['children'][0]->code,
        'depth'              => 2,
        'children_per_level' => 10,
    ]);

    $grandchildCodes = array_map(fn ($grandchild) => $grandchild->code, $fixture['grandchildren']);

    expect($result['total_at_level'])->toBe(2);
    expect(array_column($result['categories'], 'code'))->toEqualCanonicalizing($grandchildCodes);
});

it('returns an error for an unknown parent_code', function () {
    $admin = $this->loginAsAdmin();

    $result = invokeCategoryTreeTool($admin, [
        'parent_code' => 'ct_missing_'.random_int(100000, 999999),
    ]);

    expect($result)->toHaveKey('error');
    expect($result['error'])->toContain('not found');
});

it('scopes the tree to branches holding products of the given family', function () {
    $admin = $this->loginAsAdmin();

    $fixture = createCategoryTreeFixture();

    $family = AttributeFamily::factory()->create();
    $otherFamily = AttributeFamily::factory()->create();

    createProductInCategories($family, [$fixture['grandchildren'][1]->code]);
    createProductInCategories($family, [$fixture['children'][2]->code]);
    createProductInCategories($otherFamily, [$fixture['children'][3]->code]);

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['parent']->code,
        'family_code'        => $family->code,
        'depth'              => 2,
        'children_per_level' => 10,
    ]);

    expect(array_column($result['categories'], 'code'))->toEqualCanonicalizing([
        $fixture['children'][0]->code,
        $fixture['children'][2]->code,
    ]);
    expect($result['total_at_level'])->toBe(2);
    expect($result['has_more_at_level'])->toBeFalse();

    $firstChild = collect($result['categories'])->firstWhere('code', $fixture['children'][0]->code);

    expect($firstChild['total_children'])->toBe(1);
    expect(array_column($firstChild['children'], 'code'))->toBe([$fixture['grandchildren'][1]->code]);
    expect($firstChild['has_more'])->toBeFalse();
});

it('returns an empty scoped tree when the family has no categorised products', function () {
    $admin = $this->loginAsAdmin();

    $fixture = createCategoryTreeFixture();

    $family = AttributeFamily::factory()->create();

    $result = invokeCategoryTreeTool($admin, [
        'parent_code' => $fixture['parent']->code,
        'family_code' => $family->code,
    ]);

    expect($result['categories'])->toBeEmpty();
    expect($result['total_at_level'])->toBe(0);
});

it('returns an error for an unknown family_code', function () {
    $admin = $this->loginAsAdmin();

    $familyCode = 'ct_missing_family_'.random_int(100000, 999999);

    $result = invokeCategoryTreeTool($admin, ['family_code' => $familyCode]);

    expect($result)->toHaveKey('error');
    expect($result['error'])->toBe(trans('ai-agent::app.common.import-family-not-found', ['family' => $familyCode]));
});

it('ranks every sibling so a relevant category beyond the first 100 is reached', function () {
    $admin = $this->loginAsAdmin();

    $suffix = 'ct'.random_int(100000, 999999);

    $parent = Category::factory()->create(['code' => "wide_parent_{$suffix}", 'parent_id' => null]);

    for ($index = 0; $index < 105; $index++) {
        Category::factory()->create([
            'code'      => "wide_child_{$index}_{$suffix}",
            'parent_id' => $parent->id,
        ]);
    }

    $fake = fakeCategoryRelevance(["wide_child_104_{$suffix}" => 0.9]);

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $parent->code,
        'depth'              => 1,
        'children_per_level' => 1,
        'relevance_query'    => 'needle',
    ]);

    expect(array_column($result['categories'], 'code'))->toBe(["wide_child_104_{$suffix}"]);
    expect($result['total_at_level'])->toBe(105);
    expect($result['has_more_at_level'])->toBeTrue();
    expect(array_sum($fake->batchSizes))->toBe(105);
});

it('batches relevance embeddings and honours the configured candidate cap', function () {
    $admin = $this->loginAsAdmin();

    config([
        'ai-agent.category_tree.relevance_batch_size'      => 4,
        'ai-agent.category_tree.relevance_candidate_limit' => 3,
    ]);

    $fixture = createCategoryTreeFixture();

    $fake = fakeCategoryRelevance([$fixture['children'][4]->code => 0.9]);

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['parent']->code,
        'depth'              => 1,
        'children_per_level' => 1,
        'relevance_query'    => 'needle',
    ]);

    expect($fake->batchSizes)->toBe([3]);
    expect(array_column($result['categories'], 'code'))->not->toContain($fixture['children'][4]->code);

    config(['ai-agent.category_tree.relevance_candidate_limit' => 10]);

    $fake->batchSizes = [];

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['parent']->code,
        'depth'              => 1,
        'children_per_level' => 1,
        'relevance_query'    => 'needle',
    ]);

    expect($fake->batchSizes)->toBe([4, 1]);
    expect(array_column($result['categories'], 'code'))->toBe([$fixture['children'][4]->code]);
});

it('orders deeper levels by relevance when a relevance_query is given', function () {
    $admin = $this->loginAsAdmin();

    $fixture = createCategoryTreeFixture();

    $extra = Category::factory()->create([
        'code'      => 'tree_grandchild_2_'.$fixture['suffix'],
        'parent_id' => $fixture['children'][0]->id,
    ]);

    fakeCategoryRelevance([
        $fixture['children'][0]->code      => 0.9,
        $extra->code                       => 0.8,
        $fixture['grandchildren'][1]->code => 0.5,
    ]);

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['parent']->code,
        'depth'              => 2,
        'children_per_level' => 2,
        'relevance_query'    => 'needle',
    ]);

    expect($result['categories'][0]['code'])->toBe($fixture['children'][0]->code);
    expect(array_column($result['categories'][0]['children'], 'code'))->toBe([
        $extra->code,
        $fixture['grandchildren'][1]->code,
    ]);
    expect($result['categories'][0]['total_children'])->toBe(3);
    expect($result['categories'][0]['has_more'])->toBeTrue();
});

it('keeps tree order and skips embeddings when no relevance_query is given', function () {
    $admin = $this->loginAsAdmin();

    $fixture = createCategoryTreeFixture();

    $fake = fakeCategoryRelevance([$fixture['children'][4]->code => 0.9]);

    $result = invokeCategoryTreeTool($admin, [
        'parent_code'        => $fixture['parent']->code,
        'depth'              => 2,
        'children_per_level' => 2,
    ]);

    expect($fake->batchSizes)->toBe([]);
    expect(array_column($result['categories'], 'code'))->toBe([
        $fixture['children'][0]->code,
        $fixture['children'][1]->code,
    ]);
    expect(array_column($result['categories'][0]['children'], 'code'))->toBe([
        $fixture['grandchildren'][0]->code,
        $fixture['grandchildren'][1]->code,
    ]);
});

/**
 * Build a branch fixture: one parent with five children, the first child having two grandchildren.
 *
 * The parent is created as a root node so it is appended at the end of the
 * nested-set tree — inserting under an existing root on a large tree would
 * shift the _lft/_rgt values of every following node.
 *
 * @return array{parent: Category, children: array<int, Category>, grandchildren: array<int, Category>, suffix: string}
 */
function createCategoryTreeFixture(): array
{
    $suffix = 'ct'.random_int(100000, 999999);

    $parent = Category::factory()->create([
        'code'            => "tree_parent_{$suffix}",
        'parent_id'       => null,
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => "Tree Parent {$suffix}"]]],
    ]);

    $childNames = ['Child One', 'Child Two', 'Child Three', 'Child Four', 'Child Five'];
    $children = [];

    foreach ($childNames as $index => $name) {
        $children[] = Category::factory()->create([
            'code'            => "tree_child_{$index}_{$suffix}",
            'parent_id'       => $parent->id,
            'additional_data' => ['locale_specific' => ['en_US' => ['name' => "{$name} {$suffix}"]]],
        ]);
    }

    $grandchildren = [];

    foreach (['Grandchild One', 'Grandchild Two'] as $index => $name) {
        $grandchildren[] = Category::factory()->create([
            'code'            => "tree_grandchild_{$index}_{$suffix}",
            'parent_id'       => $children[0]->id,
            'additional_data' => ['locale_specific' => ['en_US' => ['name' => "{$name} {$suffix}"]]],
        ]);
    }

    return [
        'parent'        => $parent,
        'children'      => $children,
        'grandchildren' => $grandchildren,
        'suffix'        => $suffix,
    ];
}

function invokeCategoryTreeTool($admin, array $parameters): array
{
    $context = new ChatContext(
        message: 'Explore categories',
        history: [],
        productId: null,
        productSku: null,
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

    return json_decode(
        app(CategoryTree::class)->register($context)->handle(new Request($parameters)),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
}

/**
 * Create a product of the given family assigned to the given category codes.
 *
 * @param  array<int, string>  $categoryCodes
 */
function createProductInCategories(AttributeFamily $family, array $categoryCodes): Product
{
    $sku = 'CT-'.random_int(100000, 999999);

    return Product::factory()->create([
        'sku'                 => $sku,
        'attribute_family_id' => $family->id,
        'values'              => [
            'common'     => ['sku' => $sku],
            'categories' => $categoryCodes,
        ],
    ]);
}

/**
 * Bind a deterministic similarity service scoring documents by category code and recording batch sizes.
 *
 * @param  array<string, float>  $scores
 */
function fakeCategoryRelevance(array $scores): EmbeddingSimilarityService
{
    $fake = new class($scores) extends EmbeddingSimilarityService
    {
        /** @var array<int, int> */
        public array $batchSizes = [];

        /**
         * @param  array<string, float>  $scores
         */
        public function __construct(protected array $scores)
        {
            parent::__construct();
        }

        public function rank(string $query, array $documents, ?int $limit = null): array
        {
            $this->batchSizes[] = count($documents);

            $ranked = [];

            foreach ($documents as $index => $document) {
                $code = trim(explode('|', $document)[0]);

                $ranked[] = ['index' => $index, 'score' => $this->scores[$code] ?? 0.1];
            }

            usort($ranked, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

            return array_slice($ranked, 0, $limit ?? count($ranked));
        }
    };

    app()->instance(EmbeddingSimilarityService::class, $fake);

    return $fake;
}
