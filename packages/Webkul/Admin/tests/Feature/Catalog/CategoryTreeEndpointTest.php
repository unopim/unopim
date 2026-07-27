<?php

use Webkul\Category\Models\Category;

/**
 * The category picker used to answer `tree` with a full branch — every sibling at
 * every ancestor level — repeated once per selected category, each node serialized
 * as the raw model. A product sitting in 50 categories of a wide tree pulled
 * megabytes. The endpoint must now send the path down to each selection only, as
 * slim nodes, with the rest of every level left to the lazy children endpoint.
 */
function makeTreeFixture(): array
{
    $root = Category::factory()->create([
        'parent_id'       => null,
        'code'            => 'tree_root',
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => 'Tree Root'], 'fr_FR' => ['name' => 'Racine']]],
    ]);

    $branch = Category::factory()->create([
        'parent_id'       => $root->id,
        'code'            => 'tree_branch',
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => 'Branch']]],
    ]);

    $leaves = collect(range(1, 5))->map(fn (int $index) => Category::factory()->create([
        'parent_id'       => $branch->id,
        'code'            => 'tree_leaf_'.$index,
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => 'Leaf '.$index]]],
    ]));

    return [$root, $branch, $leaves];
}

it('reveals only the path to the selected categories, not their siblings', function () {
    $this->loginAsAdmin();

    [$root, $branch, $leaves] = makeTreeFixture();

    $response = $this->json('POST', route('admin.catalog.categories.tree'), [
        'locale'   => 'en_US',
        'selected' => [$leaves->first()->code],
    ]);

    $response->assertStatus(200);

    $tree = collect($response->json('selected_tree'))->firstWhere('code', $root->code);

    expect($tree)->not->toBeNull()
        ->and($tree['partial'])->toBeTrue()
        ->and($tree['children'])->toHaveCount(1)
        ->and($tree['children'][0]['code'])->toBe($branch->code)
        ->and($tree['children'][0]['children'])->toHaveCount(1)
        ->and($tree['children'][0]['children'][0]['code'])->toBe($leaves->first()->code)
        ->and($tree['children'][0]['children'][0])->not->toHaveKey('partial');
});

it('merges every selection into a single branch instead of one branch per selection', function () {
    $this->loginAsAdmin();

    [$root, $branch, $leaves] = makeTreeFixture();

    $response = $this->json('POST', route('admin.catalog.categories.tree'), [
        'locale'   => 'en_US',
        'selected' => $leaves->pluck('code')->all(),
    ]);

    $branches = collect($response->json('selected_tree'))->where('code', $root->code);

    expect($branches)->toHaveCount(1)
        ->and($branches->first()['children'][0]['children'])->toHaveCount(5);
});

it('sends tree nodes without the raw additional data payload', function () {
    $this->loginAsAdmin();

    [$root] = makeTreeFixture();

    $node = collect($this->json('POST', route('admin.catalog.categories.tree'), [
        'locale'   => 'en_US',
        'selected' => [],
    ])->json('data'))->firstWhere('code', $root->code);

    expect(array_keys($node))->toEqualCanonicalizing(['id', 'code', 'name', 'parent_id', '_lft', '_rgt'])
        ->and($node['name'])->toBe('Tree Root');
});

it('resolves node names in the requested locale', function () {
    $this->loginAsAdmin();

    [$root] = makeTreeFixture();

    $node = collect($this->json('POST', route('admin.catalog.categories.tree'), [
        'locale'   => 'fr_FR',
        'selected' => [],
    ])->json('data'))->firstWhere('code', $root->code);

    expect($node['name'])->toBe('Racine');
});

it('keeps the tree behind authentication', function () {
    $this->json('POST', route('admin.catalog.categories.tree'), ['locale' => 'en_US'])
        ->assertRedirect(route('admin.session.create'));
});

it('paginates children and reports whether more remain', function () {
    $this->loginAsAdmin();

    [, $branch] = makeTreeFixture();

    $first = $this->json('GET', route('admin.catalog.categories.children.tree', [
        'id'     => $branch->id,
        'locale' => 'en_US',
        'page'   => 1,
        'limit'  => 2,
    ]));

    $first->assertStatus(200);

    expect($first->json('data'))->toHaveCount(2)
        ->and($first->json('has_more'))->toBeTrue()
        ->and($first->json('total'))->toBe(5)
        ->and(array_keys($first->json('data.0')))->toEqualCanonicalizing(['id', 'code', 'name', 'parent_id', '_lft', '_rgt']);

    $last = $this->json('GET', route('admin.catalog.categories.children.tree', [
        'id'     => $branch->id,
        'locale' => 'en_US',
        'page'   => 3,
        'limit'  => 2,
    ]));

    expect($last->json('data'))->toHaveCount(1)
        ->and($last->json('has_more'))->toBeFalse();
});

it('caps the number of children a single request may pull', function () {
    $this->loginAsAdmin();

    [, $branch] = makeTreeFixture();

    $this->json('GET', route('admin.catalog.categories.children.tree', [
        'id'    => $branch->id,
        'page'  => 1,
        'limit' => 10000,
    ]))->assertStatus(422);
});

it('returns the breadcrumb of every search hit so identical names stay distinguishable', function () {
    $this->loginAsAdmin();

    [$root, $branch, $leaves] = makeTreeFixture();

    $response = $this->json('GET', route('admin.catalog.categories.search', [
        'query'  => 'Leaf 1',
        'locale' => 'en_US',
    ]));

    $response->assertStatus(200);

    $hit = collect($response->json('data'))->firstWhere('code', $leaves->first()->code);

    expect($hit)->not->toBeNull()
        ->and($hit['label'])->toBe('Leaf 1')
        ->and($hit['path'])->toBe('Tree Root / Branch / Leaf 1')
        ->and($response->json('page'))->toBe(1);
});
