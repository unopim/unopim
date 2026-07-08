<?php

namespace Webkul\Measurement\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class UnitsPresenter implements HistoryPresenterInterface
{
    /**
     * Human readable labels for the conversion operators.
     */
    protected static array $operatorLabels = [
        'mul' => 'Multiply',
        'div' => 'Divide',
        'add' => 'Add',
        'sub' => 'Subtract',
    ];

    /**
     * Represent the measurement family "units" JSON column as readable,
     * per-unit history rows instead of a raw array dump.
     *
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $oldUnits = static::keyByCode(static::toArray($oldValues));
        $newUnits = static::keyByCode(static::toArray($newValues));

        if (empty($oldUnits) && empty($newUnits)) {
            return [];
        }

        $normalizedData = [];

        $codes = array_values(array_unique(array_merge(
            array_keys($oldUnits),
            array_keys($newUnits)
        )));

        foreach ($codes as $code) {
            $old = isset($oldUnits[$code]) ? static::formatUnit($oldUnits[$code]) : '';
            $new = isset($newUnits[$code]) ? static::formatUnit($newUnits[$code]) : '';

            if ($old === $new) {
                continue;
            }

            $name = trans('measurement::app.measurement.edit.units').' ('.$code.')';

            $normalizedData[$name] = [
                'name' => $name,
                'old'  => $old,
                'new'  => $new,
            ];
        }

        return $normalizedData;
    }

    /**
     * Build a readable single-line representation of a unit.
     */
    protected static function formatUnit(array $unit): string
    {
        $parts = [];

        if (! empty($unit['symbol'])) {
            $parts[] = trans('measurement::app.measurement.unit.symbol').': '.$unit['symbol'];
        }

        $labels = $unit['labels'] ?? [];

        if (is_array($labels)) {
            $labelParts = [];

            foreach ($labels as $locale => $label) {
                if ($label === null || $label === '') {
                    continue;
                }

                $labelParts[] = $locale.': '.$label;
            }

            if (! empty($labelParts)) {
                $parts[] = trans('measurement::app.measurement.edit.label').': '.implode(', ', $labelParts);
            }
        }

        $conversions = $unit['convert_from_standard'] ?? [];

        if (is_array($conversions)) {
            $conversionParts = [];

            foreach ($conversions as $conversion) {
                if (! is_array($conversion)) {
                    continue;
                }

                $operator = $conversion['operator'] ?? 'mul';
                $value = $conversion['value'] ?? '';

                $conversionParts[] = (static::$operatorLabels[$operator] ?? $operator).' '.$value;
            }

            if (! empty($conversionParts)) {
                $parts[] = trans('measurement::app.measurement.unit.conversion_operation').': '.implode(' | ', $conversionParts);
            }
        }

        return implode('; ', $parts);
    }

    /**
     * Re-key a list of units by their "code" for easy comparison.
     */
    protected static function keyByCode(array $units): array
    {
        $keyed = [];

        foreach ($units as $unit) {
            if (is_array($unit) && ! empty($unit['code'])) {
                $keyed[$unit['code']] = $unit;
            }
        }

        return $keyed;
    }

    /**
     * Normalize an audit value (JSON string or array) into an array.
     */
    protected static function toArray(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }
}
