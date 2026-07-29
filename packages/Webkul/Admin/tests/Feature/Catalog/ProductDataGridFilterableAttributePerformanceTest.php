<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;

afterEach(function () {
    request()->replace([]);
});

it('does not materialize unrelated filterable attributes on the initial request', function () {
    Attribute::factory()->count(30)->create([
        'type'          => 'text',
        'is_filterable' => true,
    ]);

    request()->replace([
        'managedColumns' => ['product_id'],
    ]);

    $datagrid = app(ProductDataGrid::class);
    $datagrid->setQueryBuilder();
    $datagrid->prepareColumns();

    expect(collect($datagrid->getColumns())->pluck('index')->all())
        ->toBe(['product_id']);
});

it('materializes only the filterable attribute used by the request', function () {
    $requestedAttribute = Attribute::factory()->create([
        'type'          => 'text',
        'is_filterable' => true,
    ]);

    Attribute::factory()->count(30)->create([
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

    $datagrid = app(ProductDataGrid::class);
    $datagrid->setQueryBuilder();
    $datagrid->prepareColumns();

    $columns = collect($datagrid->getColumns());

    expect($columns->pluck('index')->all())
        ->toBe(['product_id', $requestedAttribute->code])
        ->and($columns->last()->visible)
        ->toBeFalse();
});
