<?php

namespace Webkul\Measurement\Validation;

class MeasurementUnitValidator
{
    private const CODE_REGEX = 'regex:/^(?=.*[\pL])[A-Za-z0-9_]+$/u';

    private const LABEL_REGEX = 'regex:/^(?=.*[\pL])[\pL\pN\s_]+$/u';

    private const CODE_MESSAGE = 'This field can only contain letters, numbers, and underscores.';

    private const LABEL_MESSAGE = 'This field can only contain letters, numbers, spaces, and underscores.';

    public static function storeRules(): array
    {
        return [
            'code'                    => ['required', 'string', 'max:191', self::CODE_REGEX],
            'labels'                  => ['required', 'array'],
            'labels.*'                => ['nullable', 'string', 'max:191', self::LABEL_REGEX],
            'symbol'                  => ['nullable', 'string'],
            'convert_from_standard'   => ['nullable', 'array'],
            'convert_from_standard.*' => ['nullable', 'in:mul,div,add,sub'],
            'convert_value'           => ['nullable', 'array'],
            'convert_value.*'         => ['nullable', 'numeric'],
        ];
    }

    public static function updateRules(): array
    {
        return [
            'symbol'                  => ['required', 'string'],
            'labels'                  => ['nullable', 'array'],
            'labels.*'                => ['nullable', 'string', 'max:191', self::LABEL_REGEX],
            'convert_from_standard'   => ['nullable', 'array'],
            'convert_from_standard.*' => ['nullable', 'in:mul,div,add,sub'],
            'convert_value'           => ['nullable', 'array'],
            'convert_value.*'         => ['nullable', 'numeric'],
        ];
    }

    public static function messages(): array
    {
        return [
            'code.regex'     => self::CODE_MESSAGE,
            'labels.*.regex' => self::LABEL_MESSAGE,
        ];
    }
}
