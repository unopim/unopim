<?php

namespace Webkul\Measurement\Helpers\Importers;

use Webkul\DataTransfer\Helpers\Importers\FieldProcessor as CoreFieldProcessor;
use Webkul\Measurement\Helpers\MeasurementHelper;

class FieldProcessor extends CoreFieldProcessor
{
    protected $measurementHelper;

    public function __construct()
    {
        $this->measurementHelper = app(MeasurementHelper::class);
    }

    public function handleField($field, mixed $value, ?string $path = null)
    {
        $path = $path ?? '';

        if ($field->type === 'measurement' && ! empty($value)) {

            $measurementValue = null;
            $measurementUnit = null;

            if (is_string($value)) {
                $value = str_replace('|', ',', $value);
                [$unit, $val] = array_map('trim', explode(',', $value, 2));
                $measurementValue = $val;
                $measurementUnit = $unit;
            } elseif (is_array($value) && isset($value['value'], $value['unit'])) {
                $measurementValue = $value['value'];
                $measurementUnit = $value['unit'];
            }

            if ($measurementValue !== null && $measurementUnit !== null) {
                return $this->measurementHelper->getMeasurementValueStructure(
                    (float) $measurementValue,
                    $measurementUnit,
                    $field
                );
            }

            return $value;
        }

        return parent::handleField($field, $value, $path);
    }
}
