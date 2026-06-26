<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Sources\Export\Elastic\ProductCursor;
use Webkul\Product\Models\Product;

function buildBoolQuery(array $filters): array
{
    $cursor = new ProductCursor([], null);

    $method = new ReflectionMethod($cursor, 'buildBoolQuery');
    $method->setAccessible(true);

    return $method->invoke($cursor, $filters);
}

it('builds a status all filter without a status clause', function () {
    expect(buildBoolQuery(['status' => 'all']))->toBe([]);
});

it('restricts to the ids matching the sku filter', function () {
    $family = AttributeFamily::factory()->create(['code' => 'fam_es_sku']);

    $matched = Product::create([
        'sku'                 => 'ES-SKU-1',
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    Product::create([
        'sku'                 => 'ES-SKU-2',
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    expect(buildBoolQuery(['sku' => 'ES-SKU-1']))->toBe([
        'filter' => [
            ['terms' => ['id' => [$matched->id]]],
        ],
    ]);
});

it('builds a status term clause', function () {
    expect(buildBoolQuery(['status' => 'enable']))->toBe([
        'filter' => [
            ['term' => ['status' => true]],
        ],
    ]);
});

it('builds an updated_at range clause', function () {
    expect(buildBoolQuery(['updated_after' => '2026-01-01 00:00:00']))->toBe([
        'filter' => [
            ['range' => ['updated_at' => ['gte' => '2026-01-01 00:00:00']]],
        ],
    ]);
});

it('builds an updated_at range clause with both bounds', function () {
    $filters = [
        'updated_after'  => '2026-01-01 00:00:00',
        'updated_before' => '2026-02-20 23:59:59',
    ];

    expect(buildBoolQuery($filters))->toBe([
        'filter' => [
            ['range' => ['updated_at' => ['gte' => '2026-01-01 00:00:00', 'lte' => '2026-02-20 23:59:59']]],
        ],
    ]);
});

it('builds an updated_at range clause with only an upper bound', function () {
    expect(buildBoolQuery(['updated_before' => '2026-02-20 23:59:59']))->toBe([
        'filter' => [
            ['range' => ['updated_at' => ['lte' => '2026-02-20 23:59:59']]],
        ],
    ]);
});

it('returns an empty query when no filter is set', function () {
    expect(buildBoolQuery([]))->toBe([]);
});
