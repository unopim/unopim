<?php

namespace Webkul\HistoryControl\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class VariantStructurePresenter implements HistoryPresenterInterface
{
    /**
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $normalizedData = [];

        if ($fieldName !== 'common') {
            return $normalizedData;
        }

        $old = $oldValues['VariantStructure'] ?? [];
        $new = $newValues['VariantStructure'] ?? [];

        foreach (array_keys($old + $new) as $key) {
            $oldValue = $old[$key] ?? '';
            $newValue = $new[$key] ?? '';

            if ($oldValue == $newValue) {
                continue;
            }

            $normalizedData[$key] = [
                'name' => $key,
                'new'  => $newValue,
                'old'  => $oldValue,
            ];
        }

        return $normalizedData;
    }
}
