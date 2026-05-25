<?php

namespace Webkul\Measurement\Validation;

class MeasurementFamilyValidator
{
    public static function storeRules(): array
    {
        return [
            'code'               => 'required|string|max:191|unique:measurement_families,code',
            'standard_unit_code' => 'required|string|max:191',
            'symbol'             => 'nullable|string|max:50',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'labels'   => 'nullable|array',
            'labels.*' => 'nullable|string',
        ];
    }

    public static function apiStoreRules(): array
    {
        return [
            'code'          => 'required|string|max:191',
            'name'          => 'required|string|max:191',
            'labels'        => 'required|array',
            'labels.en_US'  => 'required|string|max:191',
            'standard_unit' => 'required|string|max:191',
            'units'         => 'required|array|min:1',
            'units.*.code'  => 'required|string|max:191',
            'units.*.labels'=> 'required|array',
            'units.*.symbol'=> 'nullable|string|max:50',
            'symbol'        => 'nullable|string|max:50',
        ];
    }
}
