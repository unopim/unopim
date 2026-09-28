<?php

use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Category\Models\Category;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

class SmallBatchProductDataGrid extends ProductDataGrid
{
    const MATCHING_IDS_BATCH_SIZE = 2;
}

class SmallBatchCategoryDataGrid extends CategoryDataGrid
{
    const MATCHING_IDS_BATCH_SIZE = 2;
}

beforeEach(function () {
    app()->bind(ProductDataGrid::class, SmallBatchProductDataGrid::class);
    app()->bind(CategoryDataGrid::class, SmallBatchCategoryDataGrid::class);

    $this->loginAsAdmin();
});

function createSkuPrefixedProducts(string $prefix, int $count): array
{
    return collect(range(1, $count))
        ->map(fn (int $index) => Product::factory()->simple()->create(['sku' => $prefix.$index, 'status' => 0])->id)
        ->all();
}

it('updates the status of every product matching the filters when all matching are selected', function () {
    $matching = createSkuPrefixedProducts('SELALL-MATCH-', 5);
    $other = createSkuPrefixedProducts('SELALL-OTHER-', 2);

    $this->postJson(route('admin.catalog.products.mass_update'), [
        'select_all' => true,
        'filters'    => ['sku' => ['SELALL-MATCH-']],
        'value'      => true,
    ])->assertOk();

    expect(Product::whereIn('id', $matching)->pluck('status')->map(fn ($status) => (int) $status)->unique()->all())->toBe([1])
        ->and(Product::whereIn('id', $other)->pluck('status')->map(fn ($status) => (int) $status)->unique()->all())->toBe([0]);
});

it('deletes every product matching the filters when all matching are selected', function () {
    $matching = createSkuPrefixedProducts('SELDEL-MATCH-', 5);
    $other = createSkuPrefixedProducts('SELDEL-OTHER-', 2);

    $this->postJson(route('admin.catalog.products.mass_delete'), [
        'select_all' => true,
        'filters'    => ['sku' => ['SELDEL-MATCH-']],
    ])->assertOk();

    expect(Product::whereIn('id', $matching)->count())->toBe(0)
        ->and(Product::whereIn('id', $other)->count())->toBe(2);
});

it('still requires an id list when all matching are not selected', function () {
    $this->postJson(route('admin.catalog.products.mass_delete'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('indices');

    $this->postJson(route('admin.catalog.products.mass_update'), ['select_all' => false, 'value' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('indices');
});

it('keeps every matching product for bulk edit when all matching are selected', function () {
    $matching = createSkuPrefixedProducts('SELBULK-MATCH-', 3);
    createSkuPrefixedProducts('SELBULK-OTHER-', 2);

    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'select_all' => true,
        'filters'    => ['sku' => ['SELBULK-MATCH-']],
    ])->assertOk();

    expect(session('bulk_edit_product_ids'))->toEqualCanonicalizing($matching);
});

it('deletes every category matching the filters when all matching are selected', function () {
    $matching = collect(range(1, 3))
        ->map(fn (int $index) => Category::factory()->create(['code' => 'selcatmatch'.$index])->id)
        ->all();
    $other = Category::factory()->create(['code' => 'selcatother'])->id;

    $this->postJson(route('admin.catalog.categories.mass_delete'), [
        'select_all' => true,
        'filters'    => ['code' => ['selcatmatch']],
    ])->assertOk();

    expect(Category::whereIn('id', $matching)->count())->toBe(0)
        ->and(Category::whereKey($other)->exists())->toBeTrue();
});

it('pages matching product ids past the first Elasticsearch batch with search_after', function () {
    config([
        'elasticsearch.enabled'                     => true,
        'elasticsearch.prefix'                      => 'testing',
        'elasticsearch.connection'                  => 'default',
        'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
    ]);

    ElasticSearch::shouldReceive('makeConnection')
        ->andReturn(Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient'));

    $hit = fn (int $id): array => ['_id' => (string) $id, 'sort' => [$id]];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(fn ($args) => $args['index'] === 'testing_products'
            && $args['body']['size'] === 2
            && ! isset($args['body']['search_after']))
        ->andReturn(['hits' => ['hits' => [$hit(11), $hit(12)]]]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(fn ($args) => ($args['body']['search_after'] ?? null) === [12])
        ->andReturn(['hits' => ['hits' => [$hit(13)]]]);

    expect(app(ProductDataGrid::class)->getMatchingIds()->all())->toBe([11, 12, 13]);
});

it('accepts the select-all payload exactly as the grid sends it', function () {
    $matching = createSkuPrefixedProducts('SELBODY-MATCH-', 2);

    $this->postJson(route('admin.catalog.products.mass_update'), [
        'filters'          => ['sku' => ['SELBODY-MATCH-']],
        'managedColumns'   => [],
        'manageableColumn' => [],
        'select_all'       => 1,
        'value'            => true,
        'filter'           => null,
    ])->assertOk();

    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'filters'          => ['sku' => ['SELBODY-MATCH-']],
        'managedColumns'   => [],
        'manageableColumn' => [],
        'select_all'       => 1,
        'value'            => null,
        'filter'           => ['filtered_attributes' => []],
    ])->assertOk();

    expect(session('bulk_edit_product_ids'))->toEqualCanonicalizing($matching);

    expect(Product::whereIn('id', $matching)->pluck('status')->map(fn ($status) => (int) $status)->unique()->all())->toBe([1]);
});

it('rejects an empty sort, which is why the grid leaves it out of a select-all payload', function () {
    $this->postJson(route('admin.catalog.products.mass_update'), [
        'sort'       => [],
        'select_all' => 1,
        'value'      => true,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('sort');
});
