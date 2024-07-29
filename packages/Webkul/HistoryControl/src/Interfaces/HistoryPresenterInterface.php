<?php

namespace Webkul\HistoryControl\Interfaces;

interface HistoryPresenterInterface
{
    /**
     * return format should be similar to what is being used in normalize function of HistoryController
     * [
     *     'name'      => 'Name',
     *     'old'       => 'Default',
     *     'new'       => 'Bottle New',
     * ],
     * [
     *     'name'      => 'Root Category',
     *     'old'       => '\'\'',
     *     'new'       => 'Root',
     * ],
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array;
}
