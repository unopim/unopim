<?php

use Illuminate\Support\Arr;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\ElasticSearchQuery;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\ElasticSearch\SkuOrUniversalFilter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();

    $this->productA = Product::factory()->create([
        'sku'    => 'QSEARCHA',
        'values' => ['common' => ['sku' => 'QSEARCHA', 'product_number' => '1111111111116']],
    ]);

    $this->productB = Product::factory()->create([
        'sku'    => 'QSEARCHB',
        'values' => ['common' => ['sku' => 'QSEARCHB', 'product_number' => '2222222222223']],
    ]);

    $this->quickSearch = function (string $term): array {
        app()->forgetInstance(AttributeService::class);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), [
                'pagination' => ['page' => 1, 'per_page' => 50],
                'filters'    => ['all' => [$term]],
            ])->assertOk();

        return collect($response->json('records'))->pluck('sku')->all();
    };

    $this->setCommonValue = function (Product $product, string $code, string $value): void {
        $values = $product->values;
        $values['common'][$code] = $value;
        $product->values = $values;
        $product->save();
    };
});

it('searches the sku and not other identifiers by default', function () {
    expect(($this->quickSearch)('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(($this->quickSearch)('1111111111116'))->not->toContain('QSEARCHA');
});

it('searches the sku on an installation whose config file predates the setting', function () {
    config(['products' => Arr::except(config('products'), 'search_fields')]);

    expect(config()->has('products.search_fields'))->toBeFalse();

    expect(($this->quickSearch)('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(($this->quickSearch)('1111111111116'))->not->toContain('QSEARCHA');
});

it('ships the same default in the config file and in the class constant', function () {
    expect(config('products.search_fields'))
        ->toBe((new ReflectionClass(ProductDataGrid::class))->getConstant('DEFAULT_SEARCH_FIELDS'));
});

it('searches a configured identifier attribute too', function () {
    config(['products.search_fields' => ['sku', 'name', 'product_number']]);

    expect(($this->quickSearch)('1111111111116'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(($this->quickSearch)('QSEARCHB'))->toContain('QSEARCHB')->not->toContain('QSEARCHA');
});

it('falls back to the sku and name pair when nothing configured is searchable', function () {
    config(['products.search_fields' => ['no_such_attribute', 'price']]);

    expect(($this->quickSearch)('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(($this->quickSearch)('1111111111116'))->not->toContain('QSEARCHA');
});

it('searches text attributes but not textarea attributes', function () {
    Attribute::factory()->create(['code' => 'qsearch_text', 'type' => 'text']);
    Attribute::factory()->create(['code' => 'qsearch_textarea', 'type' => 'textarea']);

    ($this->setCommonValue)($this->productA, 'qsearch_text', 'QTEXTVALUE');
    ($this->setCommonValue)($this->productB, 'qsearch_textarea', 'QTEXTAREAVALUE');

    config(['products.search_fields' => ['sku', 'price', 'no_such_attribute', 'qsearch_text', 'qsearch_textarea']]);

    expect(($this->quickSearch)('QTEXTVALUE'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');

    expect(($this->quickSearch)('QTEXTAREAVALUE'))->not->toContain('QSEARCHB');
});

it('keeps the configured order so the cap drops the codes written last', function () {
    $cap = (new ReflectionClass(ProductDataGrid::class))->getConstant('MAX_SEARCH_FIELDS');

    foreach (range(1, $cap + 3) as $index) {
        Attribute::factory()->create(['code' => "qsearch_field_{$index}", 'type' => 'text']);
    }

    $codes = collect(range($cap + 3, 1))
        ->map(fn (int $index): string => "qsearch_field_{$index}")
        ->all();

    ($this->setCommonValue)($this->productA, $codes[$cap - 1], 'QINSIDECAP');
    ($this->setCommonValue)($this->productB, $codes[$cap], 'QOUTSIDECAP');

    config(['products.search_fields' => $codes]);

    expect(($this->quickSearch)('QINSIDECAP'))->toContain('QSEARCHA');

    expect(($this->quickSearch)('QOUTSIDECAP'))->not->toContain('QSEARCHB');
});

it('never lets the cap drop the sku out of the quick search', function () {
    $cap = (new ReflectionClass(ProductDataGrid::class))->getConstant('MAX_SEARCH_FIELDS');

    foreach (range(1, $cap) as $index) {
        Attribute::factory()->create(['code' => "qsearch_tail_{$index}", 'type' => 'text']);
    }

    $codes = collect(range(1, $cap))
        ->map(fn (int $index): string => "qsearch_tail_{$index}")
        ->all();

    $codes[] = 'sku';

    config(['products.search_fields' => $codes]);

    expect(($this->quickSearch)('QSEARCHA'))->toContain('QSEARCHA')->not->toContain('QSEARCHB');
});

it('turns the default identifier fields into elasticsearch clauses', function () {
    $query = new ElasticSearchQuery;

    $filter = resolve(SkuOrUniversalFilter::class);

    $filter->setQueryManager($query);

    $filter->applyUnfilteredFilter(['sku', 'product_number'], FilterOperators::WILDCARD, 'QSEARCHA', [
        'locale'  => 'en_US',
        'channel' => 'default',
    ]);

    $clauses = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['bool']['should'];

    expect($clauses)->toHaveCount(2);

    expect(json_encode($clauses))
        ->toContain('values.common.sku-text')
        ->toContain('values.common.product_number-text');
});
