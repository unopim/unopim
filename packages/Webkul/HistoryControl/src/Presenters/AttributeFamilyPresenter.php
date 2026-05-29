<?php

declare(strict_types=1);

namespace Webkul\HistoryControl\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class AttributeFamilyPresenter implements HistoryPresenterInterface
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

        foreach ($newValues['AttributeFamilyGroupMapping'] ?? [] as $key => $newValue) {

            $oldValue = $oldValues['AttributeFamilyGroupMapping'][$key] ?? '';

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
