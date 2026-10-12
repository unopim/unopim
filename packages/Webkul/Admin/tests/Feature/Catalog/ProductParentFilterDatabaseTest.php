<?php

use Illuminate\Testing\TestResponse;
use Webkul\Product\Models\Product;

/*
 * Filters are independent of the displayed columns: every filterable property is
 * offered whether or not the grid renders it. The Elasticsearch path already covers
 * the parent filter itself; this pins the database path, which resolves the parent
 * SKU to an id and filters products.parent_id.
 */

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();
});

function productGrid(array $data = []): TestResponse
{
    return test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.products.index'), $data + [
            'pagination' => ['page' => 1, 'per_page' => 50],
        ]);
}

function productGridColumn(TestResponse $response, string $index): ?array
{
    return collect($response->json('columns'))->firstWhere('index', $index);
}

it('returns only the variants of the requested parent sku', function () {
    $parent = Product::factory()->configurable()->withVariantProduct()->create(['sku' => 'parent-a']);
    $other = Product::factory()->configurable()->withVariantProduct()->create(['sku' => 'parent-b']);

    $expected = Product::where('parent_id', $parent->id)->pluck('sku')->all();

    expect($expected)->not->toBeEmpty();

    $response = productGrid(['filters' => ['parent' => ['parent-a']]])->assertOk();

    $skus = collect($response->json('records'))->pluck('sku')->all();

    expect($skus)->toEqualCanonicalizing($expected);

    foreach (Product::where('parent_id', $other->id)->pluck('sku')->all() as $sku) {
        expect($skus)->not->toContain($sku);
    }
});

it('offers parent as a filter without making it a grid column', function () {
    $response = productGrid()->assertOk();

    $parent = productGridColumn($response, 'parent');

    expect($parent)->not->toBeNull();
    expect($parent['filterable'])->toBeTrue();
    expect($parent['visible'])->toBeFalse();
});

it('keeps the declared filter defaults independent of the displayed columns', function () {
    $response = productGrid()->assertOk();

    $defaults = $response->json('meta.default_filters');

    expect($defaults)->toBe([
        'sku',
        'parent',
        'attribute_family',
        'type',
        'categories',
        'created_at',
        'updated_at',
    ]);

    $displayed = collect($response->json('columns'))
        ->where('visible', true)
        ->pluck('index')
        ->all();

    expect($displayed)->not->toContain('parent');
    expect($displayed)->not->toContain('categories');
});

it('marks every declared default as an on-by-default, removable filter', function () {
    $response = productGrid()->assertOk();

    foreach ($response->json('meta.default_filters') as $index) {
        $column = productGridColumn($response, $index);

        expect($column)->not->toBeNull("missing filter column: {$index}");
        expect($column['filterable'])->toBeTrue();
        expect($column['default_filter'])->toBeTrue();
        expect($column['removable_filter'])->toBeTrue();
    }
});

it('offers the remaining filterable properties through add filter without switching them on', function () {
    $response = productGrid()->assertOk();

    foreach (['status', 'completeness', 'product_id'] as $index) {
        $column = productGridColumn($response, $index);

        expect($column)->not->toBeNull("missing filter column: {$index}");
        expect($column['filterable'])->toBeTrue();
        expect($column['default_filter'])->toBeFalse();
        expect($column['removable_filter'])->toBeTrue();
    }
});

it('still renders the parent column when it is explicitly managed', function () {
    $response = productGrid(['managedColumns' => ['sku', 'parent']])->assertOk();

    $parent = productGridColumn($response, 'parent');

    expect($parent['visible'])->not->toBeFalse();
});
