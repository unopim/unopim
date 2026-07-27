<?php

namespace Webkul\Category\Rules;

use Illuminate\Validation\Rule;

class CategoryFieldValidationRules
{
    const VALIDATABLE_TYPE = 'text';

    const EMPTY_VALIDATIONS = ['', 'none'];

    const REGEX_VALIDATION = 'regex';

    const UNIQUE_TYPES = ['text', 'date', 'datetime'];

    public static function normalize(mixed $validation): mixed
    {
        return is_string($validation) && in_array($validation, self::EMPTY_VALIDATIONS, true)
            ? null
            : $validation;
    }

    public static function for(mixed $type, mixed $validation): array
    {
        if (! is_string($type) || $type !== self::VALIDATABLE_TYPE) {
            return [
                'validation'    => self::rejected(self::EMPTY_VALIDATIONS),
                'regex_pattern' => self::rejected(),
            ];
        }

        $rules = ['validation' => ['nullable']];

        if (! self::isEmpty($validation)) {
            $rules['validation'][] = new ValidationTypes;
        }

        $rules['regex_pattern'] = $validation === self::REGEX_VALIDATION
            ? ['required', 'string', 'max:255']
            : self::rejected();

        return $rules;
    }

    protected static function rejected(array $allowed = ['']): array
    {
        return ['nullable', Rule::in($allowed)];
    }

    protected static function isEmpty(mixed $validation): bool
    {
        return $validation === null || in_array($validation, self::EMPTY_VALIDATIONS, true);
    }

    public static function uniqueFlagRules(mixed $type): array
    {
        return is_string($type) && in_array($type, self::UNIQUE_TYPES, true)
            ? ['sometimes', 'boolean']
            : ['sometimes', 'boolean', Rule::in([0, '0', false])];
    }
}
