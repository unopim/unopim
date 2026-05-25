<?php

namespace Webkul\Measurement\Helpers;

use Webkul\Measurement\Repository\AttributeMeasurementRepository;

class MeasurementHelper
{
    /**
     * Attribute measurement repository instance.
     */
    protected $attributeMeasurementRepository;

    public function __construct(
        AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

    /**
     * Check whether the given attribute is measurement type.
     *
     * @param mixed $attribute
     * @return bool
     */
    public function isMeasurementAttribute($attribute)
    {
        return $this->attributeMeasurementRepository
            ->getByAttributeId($attribute->id) !== null;
    }

    /**
     * Calculate base value using unit conversion rules.
     *
     * @param mixed $value
     * @param string|null $unitCode
     * @param mixed $family
     * @return float|int
     */
    public function calculateBaseValue($value, $unitCode, $family)
    {
        if (! $family || ! $unitCode) {
            return $value;
        }

        $units = $family->units ?? [];
        $unit = collect($units)->firstWhere('code', $unitCode);

        if (! $unit) {
            return $value;
        }

        $operations = $unit['convert_from_standard'] ?? [];
        $baseValue = (float) $value;

        $reversedOps = array_reverse($operations);

        foreach ($reversedOps as $op) {
            $val = (float) $op['value'];
            $operator = $op['operator'];

            switch ($operator) {
                case 'mul':
                    $baseValue /= $val;
                    break;
                case 'div':
                    $baseValue *= $val;
                    break;
                case 'add':
                    $baseValue -= $val;
                    break;
                case 'sub':
                    $baseValue += $val;
                    break;
            }
        }

        return $baseValue;
    }

    /**
     * Prepare standardized measurement value structure.
     *
     * @param mixed $value
     * @param string|null $unit
     * @param mixed $attribute
     * @return array
     */
    public function getMeasurementValueStructure($value, $unit, $attribute)
    {
        $attributeMeasurement = $this->attributeMeasurementRepository->getByAttributeId($attribute->id);

        if (! $attributeMeasurement || ! $attributeMeasurement->family) {
            return [
                'value' => $value,
                'unit'  => $unit,
            ];
        }

        $family = $attributeMeasurement->family;
        $baseValue = $this->calculateBaseValue($value, $unit, $family);

        return [
            'unit'      => $unit,
            'amount'    => number_format((float) $value, 4, '.', ''),
            'family'    => $attributeMeasurement->family_code,
            'base_data' => number_format((float) $baseValue, 6, '.', ''),
            'base_unit' => $family->standard_unit,
        ];
    }
}
