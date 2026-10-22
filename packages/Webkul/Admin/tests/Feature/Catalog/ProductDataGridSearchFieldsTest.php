<?php

use Illuminate\Support\Arr;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\ElasticSearchQuery;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\ElasticSearch\SkuOrUniversalFilter;
use Webkul\Product\Models\Product;

/*
 * The quick search box posts its term as filters[all]. Which attribute codes it
 * looks in comes from products.search_fields; whatever is configured there, the
 * search must never degrade into matching everything or matching nothing.
 *
 * The end-to-end cases are driven on the database path, which resolves the
 * field list through the same ProductDataGrid::processFilters() call site as
 * the Elasticsearch path and needs no live index. The last case asserts that
 * the list the grid resolves is one the Elasticsearch filter turns into
 * clauses, which is the half a database-only run cannot see.
 */

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();

    Product::factory()->create([
        'sku'    => 'QSEARCHA',
        'values' => ['common' => ['sku' => 'QSEARCHA', 'product_number' => '1111111111116']],
    ]);

    Product::factory()->create([
        'sku'    => 'QSEARCHB',
        'values' => ['common' => ['sku' => 'QSEARCHB', 'product_number' => '2222222222223']],
    ]);
});

/**
 * The SKUs the quick search box returns for one term.
 *
 * @return array<int, string>
 */
function quickSearchSkus(string $term): array
{
    $response = test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.products.index'), [
            'pagination' => ['page' => 1, 'per_page' => 50],
            'filters'    => ['all' => [$term]],
        ])->assertOk();

    return collect($response->json('records'))->pluck('sku')->all();
}

/**
 * The field list the grid resolves from the current configuration.
 *
 * @return array<int, string>
 */
function resolvedSearchFields(): array
{
    app()->forgetInstance(AttributeService::class);

    $method = new ReflectionMethod(ProductDataGrid::class, 'getSearchFields');
    $method->setAccessible(true);

    return $method->invoke(app(ProductDataGrid::class));
}

it('searches the sku and not other identifiers by default', function () {
    expect(quickSearchSkus('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(quickSearchSkus('1111111111116'))->not->toContain('QSEARCHA');
});

it('searches the sku and the name on an installation whose config file predates the setting', function () {
    /*
     * The shipped config/products.php carries the key, so every other case in
     * this file reads it. An installation upgraded from an earlier release
     * does not have it: that path falls through to the class constant, and it
     * is the path every existing installation takes.
     */
    config(['products' => Arr::except(config('products'), 'search_fields')]);

    expect(config()->has('products.search_fields'))->toBeFalse();

    expect(resolvedSearchFields())->toBe(['sku', 'name']);

    expect(quickSearchSkus('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');
});

it('ships the same default in the config file and in the class constant', function () {
    /*
     * A fresh installation reads the config file, an upgraded one reads the
     * constant. They have to agree, or the two populations search different
     * fields out of the box.
     */
    expect(config('products.search_fields'))
        ->toBe((new ReflectionClass(ProductDataGrid::class))->getConstant('DEFAULT_SEARCH_FIELDS'));
});

it('searches a configured identifier attribute too', function () {
    config(['products.search_fields' => ['sku', 'name', 'product_number']]);

    expect(quickSearchSkus('1111111111116'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(quickSearchSkus('QSEARCHB'))->toContain('QSEARCHB')->not->toContain('QSEARCHA');
});

it('falls back to the sku and name pair when nothing configured is searchable', function () {
    config(['products.search_fields' => ['no_such_attribute', 'price']]);

    expect(quickSearchSkus('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(quickSearchSkus('1111111111116'))->not->toContain('QSEARCHA');
});

it('resolves only the attribute types a text search can look in', function () {
    config(['products.search_fields' => ['sku', 'price', 'color', 'no_such_attribute', 'description']]);

    /*
     * description is a textarea: a long text attribute matches most of the
     * catalogue, and the grid orders by its sort column rather than by
     * relevance, so the row that was meant would not come first.
     */
    expect(resolvedSearchFields())->toBe(['sku']);
});

it('keeps the configured order so the cap drops the codes written last', function () {
    $cap = (new ReflectionClass(ProductDataGrid::class))->getConstant('MAX_SEARCH_FIELDS');

    foreach (range(1, $cap + 3) as $index) {
        Attribute::factory()->create(['code' => "qsearch_field_{$index}", 'type' => 'text']);
    }

    /*
     * Configured in the reverse of the order they were created in on purpose:
     * AttributeService::findByCodes() hands its map back in attribute cache
     * order, so only reading the configured list keeps the administrator's own
     * ordering in charge of which codes the cap drops.
     */
    $codes = collect(range($cap + 3, 1))
        ->map(fn (int $index): string => "qsearch_field_{$index}")
        ->all();

    config(['products.search_fields' => $codes]);

    expect(resolvedSearchFields())->toBe(array_slice($codes, 0, $cap));
});

it('never lets the cap drop the sku out of the quick search', function () {
    $cap = (new ReflectionClass(ProductDataGrid::class))->getConstant('MAX_SEARCH_FIELDS');

    foreach (range(1, $cap) as $index) {
        Attribute::factory()->create(['code' => "qsearch_tail_{$index}", 'type' => 'text']);
    }

    $codes = collect(range(1, $cap))
        ->map(fn (int $index): string => "qsearch_tail_{$index}")
        ->all();

    // Written last, so an unguarded array_slice would drop the one identifier
    // the product grid cannot do without.
    $codes[] = 'sku';

    config(['products.search_fields' => $codes]);

    $fields = resolvedSearchFields();

    expect($fields)->toHaveCount($cap);
    expect($fields[0])->toBe('sku');

    expect(quickSearchSkus('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');
});

it('hands a field list the elasticsearch filter turns into clauses', function () {
    config(['products.search_fields' => ['sku', 'product_number']]);

    $fields = resolvedSearchFields();

    expect($fields)->toBe(['sku', 'product_number']);

    $query = new ElasticSearchQuery;

    $filter = resolve(SkuOrUniversalFilter::class);

    $filter->setQueryManager($query);

    $filter->applyUnfilteredFilter($fields, FilterOperators::WILDCARD, 'QSEARCHA', [
        'locale'  => 'en_US',
        'channel' => 'default',
    ]);

    $clauses = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['bool']['should'];

    expect($clauses)->toHaveCount(2);

    // Neither attribute is scoped, so the indexed path is the common one.
    expect(json_encode($clauses))
        ->toContain('values.common.sku-text')
        ->toContain('values.common.product_number-text');
});
