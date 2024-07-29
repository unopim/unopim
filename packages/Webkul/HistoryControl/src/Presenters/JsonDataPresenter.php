<?php

namespace Webkul\HistoryControl\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class JsonDataPresenter implements HistoryPresenterInterface
{
    public static $sections = [
        'locale_specific',
    ];

    public static $otherSections = [];

    public static $channelAndLocaleSpecific = 'channel_locale_specific';

    /**
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $oldArray = is_string($oldValues) ? json_decode($oldValues, true) : [];

        $newArray = is_string($newValues) ? json_decode($newValues, true) : [];

        $normalizedData = [];

        if (empty($oldArray) && empty($newArray)) {
            return $normalizedData;
        }

        $removed = array_diff($oldArray['common'] ?? [], $newArray['common'] ?? []);

        $updated = array_diff($newArray['common'] ?? [], $oldArray['common'] ?? []);

        foreach (static::$sections as $section) {
            $removed += static::getChangedValues(
                currentArray: ($oldArray[$section] ?? []),
                comparingArray: ($newArray[$section] ?? []),
                sectionName: $section,
            );

            $updated += static::getChangedValues(
                currentArray: ($newArray[$section] ?? []),
                comparingArray: ($oldArray[$section] ?? []),
                sectionName: $section,
            );
        }

        foreach (static::$otherSections as $otherSection) {
            $oldValue = '';
            $newValue = '';

            if (isset($oldArray[$otherSection])) {
                $oldValue = is_array($oldArray[$otherSection]) ? implode(', ', $oldArray[$otherSection]) : $oldArray[$otherSection];
            }

            if (isset($newArray[$otherSection])) {
                $newValue = is_array($newArray[$otherSection]) ? implode(', ', $newArray[$otherSection]) : $newArray[$otherSection];
            }

            if ($oldValue === $newValue) {
                continue;
            }

            $removed[$otherSection] = $oldValue;

            $updated[$otherSection] = $newValue;
        }

        static::normalizeValues($removed, 'old', $normalizedData);

        static::normalizeValues($updated, 'new', $normalizedData);

        return $normalizedData;
    }

    /**
     * Get Changed Values from old and new values according to sections
     */
    public static function getChangedValues(array $currentArray, array $comparingArray, string $sectionName): array
    {
        $changedValues = [];

        foreach ($currentArray as $locale => $fields) {
            if (static::$channelAndLocaleSpecific === $sectionName) {
                foreach ($fields as $channelOrLocale => $values) {
                    $changed = ! empty($comparingArray[$locale][$channelOrLocale])
                        ? array_diff($values, $comparingArray[$locale][$channelOrLocale])
                        : $values;

                    if (! empty($changed)) {
                        foreach ($changed as $key => $value) {
                            $changedValues += [
                                $key.'('.$channelOrLocale.') ('.$locale.')' => $value,
                            ];
                        }
                    }
                }

                continue;
            }

            $changed = ! empty($comparingArray[$locale])
                ? array_diff($fields, $comparingArray[$locale])
                : $fields;

            if (! empty($changed)) {
                foreach ($changed as $key => $value) {
                    $changedValues += [
                        $key.' ('.$locale.')' => $value,
                    ];
                }
            }
        }

        return $changedValues;
    }

    /**
     * Normalize data into array for history view
     */
    public static function normalizeValues(array $values, string $valueKey, array &$normalizedData): void
    {
        foreach ($values as $name => $value) {
            if (! isset($normalizedData[$name])) {
                $normalizedData[$name] = [];
            }

            $normalizedData[$name] += [
                'name'    => $name,
                $valueKey => $value,
            ];
        }
    }
}
