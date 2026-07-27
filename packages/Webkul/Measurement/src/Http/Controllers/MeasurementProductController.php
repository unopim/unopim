<?php

namespace Webkul\Measurement\Http\Controllers;

use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Webkul\Measurement\Concerns\BuildsMeasurementColumn;

/**
 * Bound over the core product controller so the "add filter" picker endpoint
 * (filterableAttributes) emits the same price-style measurement column that the
 * datagrid does, keeping both consumers of buildColumnDefinition in sync.
 */
class MeasurementProductController extends ProductController
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
