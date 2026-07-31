<?php

use Webkul\Admin\Tests\Support\CountingProductDataGrid;
use Webkul\Product\Models\Product;

it('resolves channels and locales once for the whole export, not per row (L1)', function () {
    Product::factory()->simple()->count(5)->create();

    $grid = app(CountingProductDataGrid::class);

    $queryBuilder = new ReflectionProperty($grid, 'queryBuilder');
    $queryBuilder->setAccessible(true);
    $queryBuilder->setValue($grid, $grid->prepareQueryBuilder());

    $grid->getExportableData(['pagination' => ['per_page' => 5, 'page' => 1]]);

    expect($grid->channelLocaleResolutions)->toBe(1);
});
