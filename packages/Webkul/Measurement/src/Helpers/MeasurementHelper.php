<?php

namespace Webkul\Measurement\Helpers;

use Webkul\Measurement\Repositories\AttributeMeasurementRepository;

class MeasurementHelper
{
    public function __construct(
        /**
         * Attribute measurement repository instance.
         */
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {}

    /**
     * Check whether the given attribute is measurement type.
     *
     * @param  mixed  $attribute
     */
    public function isMeasurementAttribute($attribute): bool
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

    /**
     * Resolve the display label for a unit code.
     */
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

    /**
     * Check whether the given unit value (code, symbol or label) resolves to a
     * real unit belonging to the attribute's measurement family.
     *
     * @param  mixed  $unitValue
     * @param  mixed  $attribute
     */
    public function isValidUnit($unitValue, $attribute, ?string $locale = null): bool
    {
        $attributeMeasurement = $this->attributeMeasurementRepository->getByAttributeId($attribute->id);

        if (! $attributeMeasurement || ! $attributeMeasurement->family || $unitValue === null || $unitValue === '') {
            return false;
        }

        $resolved = $this->resolveUnitCode($unitValue, $attribute, $locale);

        return collect($attributeMeasurement->family->units ?? [])
            ->contains(fn ($unit): bool => ($unit['code'] ?? null) === $resolved);
    }

    /**
     * Resolve a unit code from a code, symbol or label.
     */
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
     */
    public function getMeasurementValueStructure($value, $unit, $attribute): array
    {
        $attributeMeasurement = $this->attributeMeasurementRepository->getByAttributeId($attribute->id);

        if (! $attributeMeasurement || ! $attributeMeasurement->family) {
            return [
                'value' => $value,
                'unit'  => $unit,
            ];
        }

        return $this->buildValueStructure(
            $value,
            $this->resolveUnitCode($unit, $attribute),
            $attributeMeasurement->family_code,
            $attributeMeasurement->family
        );
    }

    /**
     * Build the stored measurement structure from an already resolved family,
     * so callers holding a cached family avoid a repeat lookup.
     */
    public function buildValueStructure(mixed $value, ?string $unit, string $familyCode, $family): array
    {
        return [
            'unit'      => $unit,
            'amount'    => $this->applyPrecision($value, 'amount'),
            'family'    => $familyCode,
            'base_data' => $this->applyPrecision($this->calculateBaseValue($value, $unit, $family), 'base'),
            'base_unit' => $family->standard_unit,
            'symbol'    => $this->getUnitSymbol($unit, $family),
        ];
    }

    /**
     * Reduce a measurement value to the configured stored precision.
     */
    public function applyPrecision(mixed $value, string $type): string
    {
        $decimals = max(0, min(10, (int) $this->getPrecisionConfig(
            $type,
            $type === 'base' ? 6 : 4
        )));

        $value = (float) $value;

        if ($this->getPrecisionConfig('strategy', 'round') === 'trim') {
            $factor = 10 ** $decimals;

            $value = ($value < 0 ? ceil($value * $factor) : floor($value * $factor)) / $factor;
        }

        return number_format($value, $decimals, '.', '');
    }

    /**
     * Read a measurement precision setting from the admin configuration,
     * falling back to the shipped default when it has never been saved.
     */
    protected function getPrecisionConfig(string $field, mixed $default): mixed
    {
        $value = core()->getConfigData("system.measurement.$field");

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Symbol configured for a unit within its measurement family.
     */
    public function getUnitSymbol(?string $unitCode, $family): ?string
    {
        if (! $unitCode || ! $family) {
            return null;
        }

        $unit = collect($family->units ?? [])->firstWhere('code', $unitCode);

        return $unit['symbol'] ?? null;
    }

    /**
     * Get the first available label for a unit.
     */
    protected function getLabelFromUnit(array $unit, ?string $locale = null): ?string
    {
        $labels = $this->getUnitLabels($unit, $locale);

        return $labels[0] ?? null;
    }

    /**
     * Get the unit labels ordered by locale preference.
     */
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

    /**
     * Normalise a unit value for comparison.
     */
    protected function normalizeUnitValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strtolower(trim((string) $value));
    }
}
