<?php

namespace Webkul\Measurement\Helpers\Importers;

use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor as CoreFieldProcessor;
use Webkul\Measurement\Helpers\MeasurementHelper;

class FieldProcessor extends CoreFieldProcessor
{
    public function __construct(
        protected MeasurementHelper $measurementHelper
    ) {}

    /**
     * Process import field value.
     */
    public function handleField($field, mixed $value, ?string $path = null)
    {
        if ($field->type === 'measurement' && ! empty($value)) {
            $measurementValue = null;
            $measurementUnit = null;

            if (is_string($value)) {
                $value = str_replace('|', ',', $value);
                [$unit, $amount] = array_map(trim(...), explode(',', $value, 2));
                $measurementValue = $amount;
                $measurementUnit = $unit;
            } elseif (is_array($value) && array_key_exists('value', $value) && array_key_exists('unit', $value)) {
                $measurementValue = $value['value'];
                $measurementUnit = $value['unit'];
            }

            if ($measurementValue !== null && $measurementUnit !== null && $measurementUnit !== '') {
                $measurementUnit = $this->measurementHelper->resolveUnitCode($measurementUnit, $field);

                return $this->measurementHelper->getMeasurementValueStructure(
                    (float) EscapeFormulaOperators::unescapeValue($measurementValue),
                    $measurementUnit,
                    $field
                );
            }

            return $value;
        }

        return parent::handleField($field, $value, $path);
    }
}
