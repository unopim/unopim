<?php

namespace Webkul\Category\Rules;

use Illuminate\Validation\Rule;

/**
 * Builds the `validation` and `regex_pattern` rules for a category field.
 *
 * Input validations constrain typed text, so only the `text` type may carry one.
 * Shared by the admin form and the API requests.
 */
class CategoryFieldValidationRules
{
    const VALIDATABLE_TYPE = 'text';

    const EMPTY_VALIDATIONS = ['', 'none'];

    const REGEX_VALIDATION = 'regex';

    /**
     * The types whose stored value is a single scalar a uniqueness rule can span.
     */
    const UNIQUE_TYPES = ['text', 'date', 'datetime'];

    /**
     * Collapse the form's "no validation" choices to null so `none` is never stored
     * and later handed to the validator as a rule name.
     */
    public static function normalize(mixed $validation): mixed
    {
        return is_string($validation) && in_array($validation, self::EMPTY_VALIDATIONS, true)
            ? null
            : $validation;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(mixed $type, mixed $validation): array
    {
        // `rules()` runs before validation, so `type` is still raw client input.
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

    /**
     * Accept only an absent or cleared value. Uses `in` rather than `prohibited`,
     * which the shipped locale files do not translate.
     *
     * @param  array<int, string>  $allowed
     * @return array<int, mixed>
     */
    protected static function rejected(array $allowed = ['']): array
    {
        return ['nullable', Rule::in($allowed)];
    }

    protected static function isEmpty(mixed $validation): bool
    {
        return $validation === null || in_array($validation, self::EMPTY_VALIDATIONS, true);
    }

    /**
     * Uniqueness spans a single typed value, so it stays editable for those types
     * and is refused for the rest.
     *
     * @return array<int, mixed>
     */
    public static function uniqueFlagRules(mixed $type): array
    {
        return is_string($type) && in_array($type, self::UNIQUE_TYPES, true)
            ? ['sometimes', 'boolean']
            : ['sometimes', 'boolean', Rule::in([0, '0', false])];
    }
}
