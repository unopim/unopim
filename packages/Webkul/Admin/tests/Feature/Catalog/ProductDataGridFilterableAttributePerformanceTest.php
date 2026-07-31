<?php

use Illuminate\Support\Collection;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;

/*
 * Property filters are a small fixed set and are always present so filtering does
 * not depend on the displayed columns. Filterable *attributes* are the unbounded
 * side — a catalog can hold tens of thousands — so they must still materialize
 * only when a request actually filters on them.
 */

afterEach(function () {
    request()->replace([]);
});

function gridColumns(): Collection
{
    $datagrid = app(ProductDataGrid::class);
    $datagrid->setQueryBuilder();
    $datagrid->prepareColumns();

    return collect($datagrid->getColumns());
}

it('does not materialize unrelated filterable attributes on the initial request', function () {
    $unrelated = Attribute::factory()->count(30)->create([
        'type'          => 'text',
        'is_filterable' => true,
    ]);

    request()->replace([
        'managedColumns' => ['product_id'],
    ]);

    $indices = gridColumns()->pluck('index')->all();

    expect($indices)->toContain('product_id');

    foreach ($unrelated->pluck('code') as $code) {
        expect($indices)->not->toContain($code);
    }
});

it('materializes only the filterable attribute used by the request', function () {
    $requestedAttribute = Attribute::factory()->create([
        'type'          => 'text',
        'is_filterable' => true,
    ]);

    $unrelated = Attribute::factory()->count(30)->create([
        'type'          => 'text',
        'is_filterable' => true,
    ]);

    request()->replace([
        'managedColumns' => ['product_id'],
        'filters'        => [
            $requestedAttribute->code => [[
                'operator' => 'eq',
                'value'    => 'blue',
            ]],
        ],
    ]);

    $columns = gridColumns();
    $indices = $columns->pluck('index')->all();

    expect($indices)->toContain($requestedAttribute->code);
    expect($columns->firstWhere('index', $requestedAttribute->code)->visible)->toBeFalse();

    foreach ($unrelated->pluck('code') as $code) {
        expect($indices)->not->toContain($code);
    }
});

it('adds the property filters as a bounded set that never grows with the catalog', function () {
    Attribute::factory()->count(30)->create([
        'type'          => 'text',
        'is_filterable' => true,
    ]);

    request()->replace([
        'managedColumns' => ['product_id'],
    ]);

    expect(gridColumns())->toHaveCount(count(app(ProductDataGrid::class)->getPropertyColumns()));
});
