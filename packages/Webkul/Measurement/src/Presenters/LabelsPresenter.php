<?php

namespace Webkul\Measurement\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class LabelsPresenter implements HistoryPresenterInterface
{
    /**
     * Represent the measurement family "labels" JSON column as readable,
     * per-locale history rows instead of a raw array dump.
     *
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $oldLabels = static::toArray($oldValues);
        $newLabels = static::toArray($newValues);

        if (empty($oldLabels) && empty($newLabels)) {
            return [];
        }

        $normalizedData = [];

        $locales = array_values(array_unique(array_merge(
            array_keys($oldLabels),
            array_keys($newLabels)
        )));

        foreach ($locales as $locale) {
            $old = $oldLabels[$locale] ?? '';
            $new = $newLabels[$locale] ?? '';

            $old = $old === null ? '' : $old;
            $new = $new === null ? '' : $new;

            if ($old === $new) {
                continue;
            }

            $name = trans('measurement::app.measurement.edit.label').' ('.$locale.')';

            $normalizedData[$name] = [
                'name' => $name,
                'old'  => $old,
                'new'  => $new,
            ];
        }

        return $normalizedData;
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
