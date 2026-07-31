<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\CategoryTree;
use Webkul\Category\Models\Category;
use Webkul\MagicAI\Models\MagicAIPlatform;

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
