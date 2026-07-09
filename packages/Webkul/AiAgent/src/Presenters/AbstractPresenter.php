<?php

namespace Webkul\AiAgent\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

abstract class AbstractPresenter implements HistoryPresenterInterface
{
    /**
     * Represent the changed flat "common" fields as old/new pairs for the history UI.
     *
     * @param  array<string, mixed>|mixed  $oldValues
     * @param  array<string, mixed>|mixed  $newValues
     * @return array<string, array{name: string, old?: mixed, new?: mixed}>
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $oldValues = is_array($oldValues) ? $oldValues : [];

        $newValues = is_array($newValues) ? $newValues : [];

        $normalizedData = [];

        foreach (array_keys($oldValues + $newValues) as $name) {
            $old = $oldValues[$name] ?? null;

            $new = $newValues[$name] ?? null;

            if ($old == $new) {
                continue;
            }

            $normalizedData[$name] = [
                'name' => $name,
                'old'  => $old,
                'new'  => $new,
            ];
        }

        return $normalizedData;
    }
}
