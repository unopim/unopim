<?php

namespace Webkul\Measurement\Validation;

class MeasurementUnitValidator
{
    private const string CODE_REGEX = 'regex:/^[A-Za-z0-9_]+$/u';

    public const MAX_CONVERSIONS = 5;

    private const string LABEL_REGEX = 'regex:/^(?=.*[\pL])[\pL\pN\pM\s_]+$/u';

    /**
     * Validation rules for creating a unit.
     */
    public static function storeRules(): array
    {
        return [
            'code'                    => ['required', 'string', 'max:191', self::CODE_REGEX],
            'labels'                  => ['required', 'array'],
            'labels.*'                => ['nullable', 'string', self::LABEL_REGEX],
            'symbol'                  => ['nullable', 'string'],
            'convert_from_standard'   => ['nullable', 'array', 'max:'.self::MAX_CONVERSIONS],
            'convert_from_standard.*' => ['nullable', 'in:mul,div,add,sub'],
            'convert_value'           => ['nullable', 'array', 'max:'.self::MAX_CONVERSIONS],
            'convert_value.*'         => ['nullable', 'numeric'],
        ];
    }

    /**
     * Validation rules for updating a unit.
     */
    public static function updateRules(): array
    {
        return [
            'symbol'                  => ['nullable', 'string'],
            'labels'                  => ['nullable', 'array'],
            'labels.*'                => ['nullable', 'string', self::LABEL_REGEX],
            'convert_from_standard'   => ['nullable', 'array', 'max:'.self::MAX_CONVERSIONS],
            'convert_from_standard.*' => ['nullable', 'in:mul,div,add,sub'],
            'convert_value'           => ['nullable', 'array', 'max:'.self::MAX_CONVERSIONS],
            'convert_value.*'         => ['nullable', 'numeric'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'convert_from_standard.max' => trans('measurement::app.validation.max_conversions', ['max' => self::MAX_CONVERSIONS]),
            'convert_value.max'         => trans('measurement::app.validation.max_conversions', ['max' => self::MAX_CONVERSIONS]),
            'code.regex'                => trans('measurement::app.validation.code_format'),
            'labels.*.regex'            => trans('measurement::app.validation.label_format'),
        ];
    }
}
