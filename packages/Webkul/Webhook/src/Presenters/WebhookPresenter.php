<?php

namespace Webkul\Webhook\Presenters;

use Webkul\HistoryControl\Presenters\JsonDataPresenter;

class WebhookPresenter extends JsonDataPresenter
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
        $oldArray = is_string($oldValues) ? json_decode($oldValues, true) : $oldValues;
        $newArray = is_string($newValues) ? json_decode($newValues, true) : $newValues;

        $normalizedData = [];

        if (empty($oldArray) && empty($newArray)) {
            return $normalizedData;
        }

        $removed = static::calculateDifference((array) $oldArray, (array) $newArray);
        $updated = static::calculateDifference((array) $newArray, (array) $oldArray);

        static::normalizeValues($removed, 'old', $normalizedData);
        static::normalizeValues($updated, 'new', $normalizedData);

        foreach ($normalizedData as $key => $value) {
            $normalizedData[$key] = [
                'name' => static::formatKey((string) $key),
                'old'  => $value['old'] ?? null,
                'new'  => $value['new'] ?? null,
            ];
        }

        return $normalizedData;
    }

    protected static function formatKey(string $key): string
    {
        return match ($key) {
            'name'      => 'Name',
            'url'       => 'URL',
            'is_active' => 'Active',
            'events'    => 'Events',
            'headers'   => 'Headers',
            default     => $key,
        };
    }
}
