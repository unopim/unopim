<?php

namespace Webkul\Measurement\DataGrids;

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Measurement\Concerns\BuildsMeasurementColumn;

/**
 * Extends the core product datagrid so filterable measurement attributes get the
 * price-style two-field filter (value input + unit dropdown) through master's
 * generic attribute-filter UI. Backend matching is handled by MeasurementFilter.
 */
class MeasurementProductDataGrid extends ProductDataGrid
{
    use BuildsMeasurementColumn;

    /**
     * {@inheritdoc}
     */
    protected function buildColumnDefinition($attribute): array
    {
        return $this->applyMeasurementColumnType(
            parent::buildColumnDefinition($attribute),
            $attribute
        );
    }
}
