<?php

namespace Webkul\DataTransfer\Tests\Support;

class BooleanImportValues
{
    /**
     * Boolean words an importer accepts, keyed by case name and paired with the integer each one is stored as.
     *
     * @return array<string, array{0: mixed, 1: int}>
     */
    public static function recognised(): array
    {
        return [
            'false'      => ['false', 0],
            'FALSE'      => ['FALSE', 0],
            'no'         => ['no', 0],
            'off'        => ['off', 0],
            '0'          => ['0', 0],
            'bool false' => [false, 0],
            'int 0'      => [0, 0],
            'true'       => ['true', 1],
            'TRUE'       => ['TRUE', 1],
            'yes'        => ['yes', 1],
            'on'         => ['on', 1],
            '1'          => ['1', 1],
            'bool true'  => [true, 1],
            'int 1'      => [1, 1],
        ];
    }

    /**
     * Values an importer must reject in a boolean column.
     *
     * @return list<string>
     */
    public static function unrecognised(): array
    {
        return ['Y', 'y', '2', 'maybe'];
    }
}
