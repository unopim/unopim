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

it('omits the parent filter entirely from the default column set', function () {
    $response = productGrid([
        'pagination' => ['page' => 1, 'per_page' => 10],
    ])->assertOk();

    $indices = collect($response->json('columns'))->pluck('index')->all();

    expect($indices)->not->toContain('parent');
});
