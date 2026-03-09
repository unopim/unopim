<?php

namespace Webkul\Measurement\Import;

use Webkul\Measurement\Helpers\MeasurementHelper;

class MeasurementProductImport
{
    protected $helper;

    public function __construct(MeasurementHelper $helper)
    {
        $this->helper = $helper;
    }

    public function handle($product, $row, $attribute)
    {
        if (! $this->helper->isMeasurementAttribute($attribute)) {
            return;
        }

        $value = $row[$attribute->code.'_value'] ?? null;
        $unit = $row[$attribute->code.'_unit'] ?? null;

        if (! $value || ! $unit) {
            return;
        }

        $json = [
            'value' => $value,
            'unit'  => $unit,
        ];

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
