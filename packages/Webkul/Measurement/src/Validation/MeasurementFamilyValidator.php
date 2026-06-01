<?php

namespace Webkul\Measurement\Validation;

class MeasurementFamilyValidator
{
    private const CODE_REGEX = 'regex:/^(?=.*[\pL])[A-Za-z0-9_]+$/u';

    private const LABEL_REGEX = 'regex:/^(?=.*[\pL])[\pL\pN\s_]+$/u';

    private const CODE_MESSAGE = 'This field can only contain letters, numbers, and underscores.';

    private const LABEL_MESSAGE = 'This field can only contain letters, numbers, spaces, and underscores.';

    public static function storeRules(): array
    {
        return [
            'code'               => ['required', 'string', 'max:191', self::CODE_REGEX, 'unique:measurement_families,code'],
            'standard_unit_code' => ['required', 'string', 'max:191', self::CODE_REGEX],
            'labels'             => ['nullable', 'array'],
            'labels.*'           => ['nullable', 'string', 'max:191', self::LABEL_REGEX],
            'unit_labels'        => ['nullable', 'array'],
            'unit_labels.*'      => ['nullable', 'string', 'max:191', self::LABEL_REGEX],
            'symbol'             => ['nullable', 'string', 'max:50'],
        ];
    }

    public static function updateRules(): array
    {
        return [
            'labels'   => ['nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:191', self::LABEL_REGEX],
        ];
    }

    public static function apiStoreRules(): array
    {
        return [
            'code'           => ['required', 'string', 'max:191', self::CODE_REGEX],
            'name'           => ['required', 'string', 'max:191', self::LABEL_REGEX],
            'labels'         => ['required', 'array'],
            'labels.en_US'   => ['required', 'string', 'max:191', self::LABEL_REGEX],
            'labels.*'       => ['nullable', 'string', 'max:191', self::LABEL_REGEX],
            'standard_unit'  => ['required', 'string', 'max:191', self::CODE_REGEX],
            'units'          => ['required', 'array', 'min:1'],
            'units.*.code'   => ['required', 'string', 'max:191', self::CODE_REGEX],
            'units.*.labels' => ['required', 'array'],
            'units.*.symbol' => ['nullable', 'string', 'max:50'],
            'symbol'         => ['nullable', 'string', 'max:50'],
        ];
    }

    public static function messages(): array
    {
        return [
            'code.regex'               => self::CODE_MESSAGE,
            'standard_unit_code.regex' => self::CODE_MESSAGE,
            'labels.*.regex'           => self::LABEL_MESSAGE,
            'unit_labels.*.regex'      => self::LABEL_MESSAGE,
            'name.regex'               => self::LABEL_MESSAGE,
            'standard_unit.regex'      => self::CODE_MESSAGE,
            'units.*.code.regex'       => self::CODE_MESSAGE,
            'labels.en_US.regex'       => self::LABEL_MESSAGE,
        ];
    }
}
