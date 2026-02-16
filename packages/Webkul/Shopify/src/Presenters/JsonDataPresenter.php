<?php

namespace Webkul\Shopify\Presenters;

use Webkul\HistoryControl\Presenters\JsonDataPresenter as JsonDataPresenters;

class JsonDataPresenter extends JsonDataPresenters
{
    /**
     * Represents value changes for history tracking.
     *
     * @param  mixed  $oldValues  Old values that will be compared.
     * @param  mixed  $newValues  New values to compare against old values.
     * @param  string  $fieldName  Name of the field being tracked.
     * @return array Normalized array of changes for history tracking.
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $oldArray = is_string($oldValues) ? json_decode($oldValues, true) : [];
        $newArray = is_string($newValues) ? json_decode($newValues, true) : [];
        $normalizedData = [];
        $arrayCheck = array_filter($oldArray, 'is_array');

        if (count($arrayCheck) > 0) {
            $oldArray = array_merge(...array_values($oldArray));
            $newArray = array_merge(...array_values($newArray));
        }

        if (empty($oldArray) && empty($newArray)) {
            return $normalizedData;
        }

        $removed = static::calculateDifference($oldArray, $newArray);
        $updated = static::calculateDifference($newArray, $oldArray);
        static::normalizeValues($removed, 'old', $normalizedData);
        static::normalizeValues($updated, 'new', $normalizedData);

        return $normalizedData;
    }
}
