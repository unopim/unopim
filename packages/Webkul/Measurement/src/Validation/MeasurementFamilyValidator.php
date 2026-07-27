<?php

namespace Webkul\Measurement\Validation;

class MeasurementFamilyValidator
{
    private const string CODE_REGEX = 'regex:/^[A-Za-z0-9_]+$/u';

    public const MAX_UNITS = 100;

    public const MAX_FAMILIES = 300;

    private const string LABEL_REGEX = 'regex:/^(?=.*[\pL])[\pL\pN\pM\s_]+$/u';

    /**
     * Validation rules for creating a family.
     */
    public static function storeRules(): array
    {
        return [
            'code'               => ['required', 'string', 'max:191', self::CODE_REGEX, 'unique:measurement_families,code'],
            'standard_unit_code' => ['required', 'string', 'max:191', self::CODE_REGEX],
            'labels'             => ['nullable', 'array'],
            'labels.*'           => ['nullable', 'string', self::LABEL_REGEX],
            'unit_labels'        => ['nullable', 'array'],
            'unit_labels.*'      => ['nullable', 'string', self::LABEL_REGEX],
            'symbol'             => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Validation rules for updating a family.
     */
    public static function updateRules(): array
    {
        return [
            'labels'   => ['nullable', 'array'],
            'labels.*' => ['nullable', 'string'],
        ];
    }

    /**
     * Validation rules for creating a family through the API.
     */
    public static function apiStoreRules(): array
    {
        return [
            'code'                          => ['required', 'string', 'max:191', self::CODE_REGEX, 'unique:measurement_families,code'],
            'name'                          => ['required', 'string', 'max:191', self::LABEL_REGEX],
            'labels'                        => ['required', 'array'],
            'labels.en_US'                  => ['required', 'string'],
            'labels.*'                      => ['nullable', 'string'],
            'standard_unit'                 => ['required', 'string', 'max:191', self::CODE_REGEX],
            'units'                         => ['required', 'array', 'min:1', 'max:'.self::MAX_UNITS],
            'units.*.code'                  => ['required', 'string', 'max:191', self::CODE_REGEX],
            'units.*.labels'                => ['required', 'array'],
            'units.*.symbol'                => ['nullable', 'string', 'max:50'],
            'units.*.convert_from_standard' => ['nullable', 'array', 'max:'.MeasurementUnitValidator::MAX_CONVERSIONS],
            'symbol'                        => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Partial-update rules for the API. Every field is optional, but when present
     * it must still be valid; `code` stays unique while ignoring the current row.
     */
    public static function apiUpdateRules($id): array
    {
        return [
            'code'                          => ['sometimes', 'required', 'string', 'max:191', self::CODE_REGEX, 'unique:measurement_families,code,'.$id],
            'name'                          => ['sometimes', 'required', 'string', 'max:191', self::LABEL_REGEX],
            'standard_unit'                 => ['sometimes', 'required', 'string', 'max:191', self::CODE_REGEX],
            'labels'                        => ['sometimes', 'array'],
            'labels.*'                      => ['nullable', 'string', self::LABEL_REGEX],
            'units'                         => ['sometimes', 'array', 'min:1', 'max:'.self::MAX_UNITS],
            'units.*.code'                  => ['required_with:units', 'string', 'max:191', self::CODE_REGEX],
            'units.*.labels'                => ['sometimes', 'array'],
            'units.*.symbol'                => ['nullable', 'string', 'max:50'],
            'units.*.convert_from_standard' => ['nullable', 'array', 'max:'.MeasurementUnitValidator::MAX_CONVERSIONS],
            'symbol'                        => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'units.max'                         => trans('measurement::app.validation.max_units', ['max' => self::MAX_UNITS]),
            'units.*.convert_from_standard.max' => trans('measurement::app.validation.max_conversions', ['max' => MeasurementUnitValidator::MAX_CONVERSIONS]),
            'code.regex'                        => trans('measurement::app.validation.code_format'),
            'standard_unit_code.regex'          => trans('measurement::app.validation.code_format'),
            'name.regex'                        => trans('measurement::app.validation.label_format'),
            'standard_unit.regex'               => trans('measurement::app.validation.code_format'),
            'units.*.code.regex'                => trans('measurement::app.validation.code_format'),
            'labels.*.regex'                    => trans('measurement::app.validation.label_format'),
            'unit_labels.*.regex'               => trans('measurement::app.validation.label_format'),
        ];
    }
}
