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
     * @param  mixed  $attribute
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
     * @param  mixed  $value
     * @param  string|null  $unitCode
     * @param  mixed  $family
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

        foreach (array_reverse($operations) as $op) {

            $val = (float) ($op['value'] ?? 0);

            $operator = $op['operator'] ?? null;

            if (
                in_array($operator, ['mul', 'div'])
                && $val == 0
            ) {
                continue;
            }

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

    public function getUnitLabel($unitCode, $attribute, ?string $locale = null)
    {
        $attributeMeasurement = $this->attributeMeasurementRepository->getByAttributeId($attribute->id);

        if (! $attributeMeasurement || ! $attributeMeasurement->family || ! $unitCode) {
            return $unitCode;
        }

        $unit = collect($attributeMeasurement->family->units ?? [])
            ->firstWhere('code', $unitCode);

        if (! $unit) {
            return $unitCode;
        }

        return $this->getLabelFromUnit($unit, $locale) ?? $unitCode;
    }

    public function resolveUnitCode($unitValue, $attribute, ?string $locale = null)
    {
        $attributeMeasurement = $this->attributeMeasurementRepository->getByAttributeId($attribute->id);

        if (! $attributeMeasurement || ! $attributeMeasurement->family || ! $unitValue) {
            return $unitValue;
        }

        $normalizedUnitValue = $this->normalizeUnitValue($unitValue);

        foreach ($attributeMeasurement->family->units ?? [] as $unit) {
            if ($this->normalizeUnitValue($unit['code'] ?? null) === $normalizedUnitValue) {
                return $unit['code'];
            }

            if ($this->normalizeUnitValue($unit['symbol'] ?? null) === $normalizedUnitValue) {
                return $unit['code'];
            }

            foreach ($this->getUnitLabels($unit, $locale) as $label) {
                if ($this->normalizeUnitValue($label) === $normalizedUnitValue) {
                    return $unit['code'];
                }
            }
        }

        return $unitValue;
    }

    /**
     * Prepare standardized measurement value structure.
     *
     * @param  mixed  $value
     * @param  string|null  $unit
     * @param  mixed  $attribute
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
        $unit = $this->resolveUnitCode($unit, $attribute);
        $baseValue = $this->calculateBaseValue($value, $unit, $family);

        return [
            'unit'      => $unit,
            'amount'    => number_format((float) $value, 4, '.', ''),
            'family'    => $attributeMeasurement->family_code,
            'base_data' => number_format((float) $baseValue, 6, '.', ''),
            'base_unit' => $family->standard_unit,
        ];
    }

    protected function getLabelFromUnit(array $unit, ?string $locale = null): ?string
    {
        $labels = $this->getUnitLabels($unit, $locale);

        return $labels[0] ?? null;
    }

    protected function getUnitLabels(array $unit, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $labels = $unit['labels'] ?? $unit['label'] ?? $unit['name'] ?? [];

        if (is_string($labels)) {
            return [$labels];
        }

        if (! is_array($labels)) {
            return [];
        }

        $orderedLabels = [];

        foreach (array_filter([$locale, 'en_US']) as $labelLocale) {
            if (! empty($labels[$labelLocale])) {
                $orderedLabels[] = $labels[$labelLocale];
            }
        }

        foreach ($labels as $label) {
            if (! empty($label)) {
                $orderedLabels[] = $label;
            }
        }

        return array_values(array_unique($orderedLabels));
    }

    protected function normalizeUnitValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strtolower(trim((string) $value));
    }
}
