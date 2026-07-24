<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Product\Models\Product;

/*
 * Guards L1: the export loop resolved channels + their locales via
 * getAllChannelsAndLocales() once per product row. The channel/locale set is
 * constant across the export, so it must be resolved exactly once regardless of
 * row count. (A raw query count cannot catch this under the array cache driver,
 * which masks the per-row lazy loads that surface on a serializing cache in
 * production — hence the call-count guard.)
 */
class CountingProductDataGrid extends ProductDataGrid
{
    public int $channelLocaleResolutions = 0;

    protected function getAllChannelsAndLocales(): array
    {
        $this->channelLocaleResolutions++;

        return parent::getAllChannelsAndLocales();
    }
}

it('resolves channels and locales once for the whole export, not per row (L1)', function () {
    Product::factory()->simple()->count(5)->create();

    $grid = app(CountingProductDataGrid::class);

    $queryBuilder = new ReflectionProperty($grid, 'queryBuilder');
    $queryBuilder->setAccessible(true);
    $queryBuilder->setValue($grid, $grid->prepareQueryBuilder());

    $grid->getExportableData(['pagination' => ['per_page' => 5, 'page' => 1]]);

    expect($grid->channelLocaleResolutions)->toBe(1);
});
