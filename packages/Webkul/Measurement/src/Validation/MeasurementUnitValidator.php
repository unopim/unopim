<?php

namespace Webkul\Measurement\Validation;

class MeasurementUnitValidator
{
    public static function storeRules(): array
    {
        return [
            'code'                    => 'required|string',
            'labels'                  => 'required|array',
            'labels.*'                => 'nullable|string',
            'symbol'                  => 'nullable|string',
            'convert_from_standard'   => 'nullable|array',
            'convert_from_standard.*' => 'nullable|in:mul,div,add,sub',
            'convert_value'           => 'nullable|array',
            'convert_value.*'         => 'nullable|numeric',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'symbol'                  => 'required|string',
            'labels'                  => 'nullable|array',
            'labels.*'                => 'nullable|string',
            'convert_from_standard'   => 'nullable|array',
            'convert_from_standard.*' => 'nullable|in:mul,div,add,sub',
            'convert_value'           => 'nullable|array',
            'convert_value.*'         => 'nullable|numeric',
        ];
    }
}
