<?php

namespace Webkul\HistoryControl\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class BooleanPresenter implements HistoryPresenterInterface
{
    /**
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        return [
            $fieldName => [
                'name' => $fieldName,
                'old'  => (bool) $oldValues,
                'new'  => (bool) $newValues,
            ],
        ];
    }
}
