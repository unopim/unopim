<?php

namespace Webkul\Measurement\Import;

use Webkul\Measurement\Helpers\MeasurementHelper;

class MeasurementProductImport
{
    public function __construct(protected MeasurementHelper $helper) {}

    /**
     * Handle measurement attribute import for product.
     *
     * @param  mixed  $product
     * @param  array  $row
     * @param  mixed  $attribute
     */
    public function handle($product, $row, $attribute): void
    {
        if (! $this->helper->isMeasurementAttribute($attribute)) {
            return;
        }

        $value = $row[$attribute->code.'_value'] ?? null;
        $unit = $row[$attribute->code.'_unit'] ?? null;

        if (! $value || ! $unit) {
            return;
        }

        $json = $this->helper->getMeasurementValueStructure(
            $value,
            $this->helper->resolveUnitCode($unit, $attribute, $row['locale'] ?? null),
            $attribute
        );

        $product->attribute_values()->updateOrCreate(
            [
                'attribute_id' => $attribute->id,
                'channel'      => $row['channel'] ?? 'default',
                'locale'       => $row['locale'] ?? 'en_US',
            ],
            [
                'value' => json_encode($json),
            ]
        );
    }
}
