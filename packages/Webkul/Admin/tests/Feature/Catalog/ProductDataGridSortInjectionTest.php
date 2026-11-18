<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;

function sortedProductSql(array $sort): string
{
    $grid = app(ProductDataGrid::class);

    $queryBuilder = new ReflectionProperty($grid, 'queryBuilder');
    $queryBuilder->setAccessible(true);
    $queryBuilder->setValue($grid, DB::table('products'));

    return $grid->processRequestedSorting($sort)->toSql();
}

it('does not let a malicious sort order inject SQL into the ORDER BY clause', function () {
    $payload = 'asc,(SELECT CASE WHEN (1=1) THEN name ELSE id END FROM admins LIMIT 1)';

    $sql = sortedProductSql(['column' => 'name', 'order' => $payload]);

    expect($sql)->not->toContain('SELECT CASE WHEN');
    expect($sql)->not->toContain('admins');
    expect(strtolower($sql))->toContain('desc');
});

it('preserves a valid ascending sort', function () {
    $sql = strtolower(sortedProductSql(['column' => 'name', 'order' => 'asc']));

    expect($sql)->toContain('asc');
    expect($sql)->not->toContain('select case when');
});

it('preserves a valid descending sort', function () {
    $sql = strtolower(sortedProductSql(['column' => 'name', 'order' => 'desc']));

    expect($sql)->toContain('desc');
});

it('sorts measurement attributes numerically by their normalised base value', function () {
    $attribute = Attribute::factory()->create([
        'code' => 'measurement_sort_'.Str::random(8),
        'type' => Attribute::MEASUREMENT_FIELD_TYPE,
    ]);

    $sql = strtolower(sortedProductSql(['column' => $attribute->code, 'order' => 'asc']));

    expect($sql)->toContain('base_data')
        // own, parent and grandparent value (variants inherit); PostgreSQL repeats each for its guard
        ->and(substr_count($sql, 'base_data'))->toBeGreaterThanOrEqual(3)
        ->and($sql)->toContain('coalesce(')
        ->and($sql)->toContain('grandparent_products')
        ->and($sql)->toContain('decimal')
        ->and($sql)->toContain('asc');
});

it('casts decimal-validated attributes before sorting', function () {
    $attribute = Attribute::factory()->validation_decimal()->create([
        'code' => 'decimal_sort_'.Str::random(8),
        'type' => 'text',
    ]);

    $sql = strtolower(sortedProductSql(['column' => $attribute->code, 'order' => 'asc']));

    expect($sql)->toContain('decimal');
});

it('sorts measurement attributes numerically in Elasticsearch instead of alphabetically', function () {
    $attribute = Attribute::factory()->create([
        'code' => 'measurement_es_'.Str::random(8),
        'type' => Attribute::MEASUREMENT_FIELD_TYPE,
    ]);

    $grid = app(ProductDataGrid::class);

    $isMeasurement = new ReflectionMethod($grid, 'isMeasurementColumn');
    $isMeasurement->setAccessible(true);

    $script = new ReflectionMethod($grid, 'numericScriptSort');
    $script->setAccessible(true);

    expect($isMeasurement->invoke($grid, $attribute->code))->toBeTrue()
        ->and($isMeasurement->invoke($grid, 'sku'))->toBeFalse();

    $asc = $script->invoke($grid, 'values.common.w-measurement.base_data', 'asc');
    $desc = $script->invoke($grid, 'values.common.w-measurement.base_data', 'desc');

    expect($asc['type'])->toBe('number')
        ->and($asc['script']['source'])->toContain('Double.parseDouble')
        ->and($asc['script']['params']['field'])->toBe('values.common.w-measurement.base_data')
        // rows without a value stay last in both directions
        ->and($asc['script']['params']['missing'])->toBeGreaterThan(0)
        ->and($desc['script']['params']['missing'])->toBeLessThan(0);
});
