<?php

namespace Webkul\Admin\Tests\Support;

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;

/**
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
