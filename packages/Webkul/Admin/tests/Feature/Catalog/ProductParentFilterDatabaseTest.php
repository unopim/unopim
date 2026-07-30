<?php

use Illuminate\Testing\TestResponse;
use Webkul\Product\Models\Product;

/*
 * The Elasticsearch path already covers the parent filter; this pins the database
 * path, which resolves the parent SKU to an id and filters products.parent_id.
 */

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();
});

function productGrid(array $data): TestResponse
{
    return test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.products.index'), $data);
}

it('returns only the variants of the requested parent sku', function () {
    $parent = Product::factory()->configurable()->withVariantProduct()->create(['sku' => 'parent-a']);
    $other = Product::factory()->configurable()->withVariantProduct()->create(['sku' => 'parent-b']);

    $expected = Product::where('parent_id', $parent->id)->pluck('sku')->all();

    expect($expected)->not->toBeEmpty();

    $response = productGrid([
        'pagination'     => ['page' => 1, 'per_page' => 50],
        'managedColumns' => ['sku', 'parent'],
        'filters'        => ['parent' => ['parent-a']],
    ])->assertOk();

    $skus = collect($response->json('records'))->pluck('sku')->all();

    expect($skus)->toEqualCanonicalizing($expected);

    $otherSkus = Product::where('parent_id', $other->id)->pluck('sku')->all();

    foreach ($otherSkus as $sku) {
        expect($skus)->not->toContain($sku);
    }
});

it('exposes parent as a filterable column once it is managed', function () {
    $response = productGrid([
        'pagination'     => ['page' => 1, 'per_page' => 10],
        'managedColumns' => ['sku', 'parent'],
    ])->assertOk();

    $parentColumn = collect($response->json('columns'))->firstWhere('index', 'parent');

    expect($parentColumn)->not->toBeNull();
    expect($parentColumn['filterable'])->toBeTrue();
});

it('offers parent in the default grid so the filter is reachable without managing columns', function () {
    $response = productGrid([
        'pagination' => ['page' => 1, 'per_page' => 10],
    ])->assertOk();

    $parentColumn = collect($response->json('columns'))->firstWhere('index', 'parent');

    expect($parentColumn)->not->toBeNull();
    expect($parentColumn['filterable'])->toBeTrue();
});

/*
 * default_filter keeps parent out of the always-on filter bar while leaving it in the
 * "Add Filter" list, and removable_filter lets it be dismissed again once applied.
 */
it('leaves the parent filter opt-in rather than pinning it to the filter bar', function () {
    $response = productGrid([
        'pagination' => ['page' => 1, 'per_page' => 10],
    ])->assertOk();

    $parentColumn = collect($response->json('columns'))->firstWhere('index', 'parent');

    expect($parentColumn['default_filter'])->toBeFalse();
    expect($parentColumn['removable_filter'])->toBeTrue();
});

it('filters by parent without the caller managing columns', function () {
    $parent = Product::factory()->configurable()->withVariantProduct()->create(['sku' => 'parent-c']);

    $expected = Product::where('parent_id', $parent->id)->pluck('sku')->all();

    $response = productGrid([
        'pagination' => ['page' => 1, 'per_page' => 50],
        'filters'    => ['parent' => ['parent-c']],
    ])->assertOk();

    expect(collect($response->json('records'))->pluck('sku')->all())
        ->toEqualCanonicalizing($expected);
});
