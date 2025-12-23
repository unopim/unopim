<?php

namespace Webkul\Webhook\Helpers;

class ProductComparer
{
    public static $sections = [
        'locale_specific',
        'channel_specific',
        'channel_locale_specific',
        'associations',
    ];

    public static $otherSections = [
        'categories',
    ];

    public static $channelAndLocaleSpecific = 'channel_locale_specific';

    public static function compare(mixed $oldValues, mixed $newValues): array
    {
        $diff = ! empty($oldValues['values']) || ! empty($newValues['values']) ? static::compareValues($oldValues['values'] ?? [], $newValues['values'] ?? []) : [];

        if (! empty($oldValues['status'])) {
            $diff['changed']['status'] = [
                'old' => $oldValues['status'],
                'new' => $newValues['status'] ?? null,
            ];
        }

        return $diff;
    }

    /**
     * Returns diff in structured format: added / removed / changed
     */
    public static function compareValues(mixed $oldValues, mixed $newValues): array
    {
        $oldArray = is_string($oldValues) ? json_decode($oldValues, true) : [];
        $newArray = is_string($newValues) ? json_decode($newValues, true) : [];

        $diff = [
            'added'   => [],
            'removed' => [],
            'changed' => [],
        ];

        static::computeCommonDiff($diff, $oldArray['common'] ?? [], $newArray['common'] ?? []);

        foreach (static::$sections as $section) {
            static::computeDiffForSection($diff, $oldArray[$section] ?? [], $newArray[$section] ?? [], $section);
        }

        foreach (static::$otherSections as $section) {
            static::computeOtherSectionDiff($diff, $oldArray[$section] ?? [], $newArray[$section] ?? [], $section);
        }

        return $diff;
    }

    /**
     * Handle common keys diff
     */
    protected static function computeCommonDiff(array &$diff, array $oldCommon, array $newCommon): void
    {
        foreach ($oldCommon as $key => $oldValue) {
            if (! isset($newCommon[$key])) {
                $diff['removed']['common'][$key] = $oldValue;

                continue;
            }

            if ($oldValue !== $newCommon[$key]) {
                $diff['changed']['common'][$key] = [
                    'old' => $oldValue,
                    'new' => $newCommon[$key],
                ];
            }
        }

        foreach ($newCommon as $key => $newValue) {
            if (! isset($oldCommon[$key])) {
                $diff['added']['common'][$key] = $newValue;
            }
        }
    }

    protected static function computeDiffForSection(array &$diff, array $oldSection, array $newSection, string $sectionName): void
    {
        if ($sectionName === static::$channelAndLocaleSpecific) {
            static::computeChannelLocaleDiff($diff, $oldSection, $newSection, $sectionName);

            return;
        }

        foreach ($oldSection as $key => $attrs) {
            foreach ($attrs as $attr => $value) {
                $newValue = $newSection[$key][$attr] ?? null;
                if ($newValue === null) {
                    $diff['removed'][$sectionName][$key][$attr] = $value;

                    continue;
                }

                if ($value !== $newValue) {
                    $diff['changed'][$sectionName][$key][$attr] = [
                        'old' => $value,
                        'new' => $newValue,
                    ];
                }
            }
        }

        foreach ($newSection as $key => $attrs) {
            foreach ($attrs as $attr => $value) {
                if (! isset($oldSection[$key][$attr])) {
                    $diff['added'][$sectionName][$key][$attr] = $value;
                }
            }
        }
    }

    /**
     * Handle channel_locale_specific section
     */
    protected static function computeChannelLocaleDiff(array &$diff, array $oldSection, array $newSection, string $sectionName): void
    {
        foreach ($oldSection as $channel => $locales) {
            foreach ($locales as $locale => $attrs) {
                foreach ($attrs as $attr => $value) {
                    $newValue = $newSection[$channel][$locale][$attr] ?? null;

                    if ($newValue === null) {
                        $diff['removed'][$sectionName][$channel][$locale][$attr] = $value;

                        continue;
                    }

                    if ($value !== $newValue) {
                        $diff['changed'][$sectionName][$channel][$locale][$attr] = [
                            'old' => $value,
                            'new' => $newValue,
                        ];
                    }
                }
            }
        }

        foreach ($newSection as $channel => $locales) {
            foreach ($locales as $locale => $attrs) {
                foreach ($attrs as $attr => $value) {
                    if (! isset($oldSection[$channel][$locale][$attr])) {
                        $diff['added'][$sectionName][$channel][$locale][$attr] = $value;
                    }
                }
            }
        }
    }

    /**
     * Handle other sections
     */
    protected static function computeOtherSectionDiff(array &$diff, mixed $oldSection, mixed $newSection, string $sectionName): void
    {
        if (is_array($oldSection) && isset($oldSection[0])) {
            $removed = array_diff($oldSection, $newSection);
            $added = array_diff($newSection, $oldSection);

            if (! empty($removed)) {
                $diff['removed'][$sectionName] = array_values($removed);
            }

            if (! empty($added)) {
                $diff['added'][$sectionName] = array_values($added);
            }

            return;
        }

        if ($oldSection !== $newSection) {
            $diff['changed'][$sectionName] = [
                'old' => $oldSection,
                'new' => $newSection,
            ];
        }
    }
}
