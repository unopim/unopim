<?php

use Illuminate\Support\Facades\DB;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;

/**
 * Regression coverage for the ORDER BY SQL injection in the product grid.
 *
 * `processRequestedSorting()` concatenated the request-supplied sort direction straight
 * into orderByRaw(). The fix allowlists the direction to asc/desc, so a malicious
 * `sort[order]` can never inject SQL into the ORDER BY clause.
 */
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
