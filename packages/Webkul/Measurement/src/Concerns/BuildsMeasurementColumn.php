<?php

namespace Webkul\Measurement\Concerns;

use Webkul\Measurement\Repositories\AttributeMeasurementRepository;

trait BuildsMeasurementColumn
{
    /**
     * Reuse the price-style two-field filter (value input + unit dropdown) for a
     * measurement column so it renders through master's generic attribute-filter
     * UI. The column keeps its 'measurement' attribute_type (set by the trait) so
     * the measurement operator group is applied; the 'price' column type is only
     * what makes the generic UI show the leading unit dropdown.
     */
    protected function applyMeasurementColumnType(array $column, $attribute): array
    {
        if ($attribute->type === 'measurement') {
            $column['type'] = 'price';
            $column['options'] = $this->getMeasurementUnitOptions($attribute);
        }

        return $column;
    }

    /**
     * Build the unit dropdown options for a measurement attribute.
     */
    protected function getMeasurementUnitOptions($attribute): array
    {
        $measurement = resolve(AttributeMeasurementRepository::class)
            ->getByAttributeId($attribute->id);

        if (! $measurement || ! $measurement->family) {
            return [];
        }

        $locale = core()->getRequestedLocaleCode();

        return collect($measurement->family->units ?? [])
            ->map(function ($unit) use ($locale): array {
                $label = $unit['labels'][$locale] ?? null;

                if (empty($label)) {
                    $label = empty($unit['symbol']) ? $unit['code'] ?? '' : ($unit['symbol']);
                }

                return [
                    'label' => $label,
                    'value' => $unit['code'] ?? '',
                ];
            })
            ->filter(fn ($option): bool => $option['value'] !== '')
            ->values()
            ->all();
    }
}
