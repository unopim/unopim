<?php

namespace Webkul\ChannelConnector\Services;

use Webkul\ChannelConnector\ValueObjects\ValidationResult;

class ValidationEngine
{
    /**
     * Validate a sync payload against a set of rules.
     *
     * Rules are keyed by field name and contain arrays of rule objects:
     * [
     *     'title' => [
     *         ['type' => 'required'],
     *         ['type' => 'min_length', 'config' => ['length' => 3]],
     *     ],
     * ]
     *
     * @param  array  $payload  The sync payload with 'common' and 'locales' keys
     * @param  array  $rules  Validation rules keyed by field name
     */
    public static function validate(array $payload, array $rules): ValidationResult
    {
        $errors = [];

        $commonValues = $payload['common'] ?? [];
        $localeValues = $payload['locales'] ?? [];

        // Validate common values
        foreach ($rules as $field => $fieldRules) {
            $value = $commonValues[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = self::validateField($field, $value, $rule);

                if ($error !== null) {
                    $errors[] = $error;
                }
            }
        }

        // Validate locale-specific values
        foreach ($localeValues as $locale => $localeFields) {
            foreach ($rules as $field => $fieldRules) {
                $value = $localeFields[$field] ?? null;

                foreach ($fieldRules as $rule) {
                    $error = self::validateField("{$field} ({$locale})", $value, $rule);

                    if ($error !== null) {
                        $errors[] = $error;
                    }
                }
            }
        }

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
        );
    }

    /**
     * Validate a single field value against a single rule.
     *
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateField(string $field, mixed $value, array $rule): ?array
    {
        $type = $rule['type'] ?? null;
        $config = $rule['config'] ?? [];

        if (! $type) {
            return null;
        }

        return match ($type) {
            'required'      => self::validateRequired($field, $value),
            'min_length'    => self::validateMinLength($field, $value, $config),
            'max_length'    => self::validateMaxLength($field, $value, $config),
            'regex'         => self::validateRegex($field, $value, $config),
            'numeric_range' => self::validateNumericRange($field, $value, $config),
            'in_list'       => self::validateInList($field, $value, $config),
            'url'           => self::validateUrl($field, $value),
            default         => null,
        };
    }

    /**
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateRequired(string $field, mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return [
                'field'   => $field,
                'rule'    => 'required',
                'message' => "The field '{$field}' is required.",
            ];
        }

        return null;
    }

    /**
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateMinLength(string $field, mixed $value, array $config): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $length = (int) ($config['length'] ?? 0);

        if (is_string($value) && mb_strlen($value) < $length) {
            return [
                'field'   => $field,
                'rule'    => 'min_length',
                'message' => "The field '{$field}' must be at least {$length} characters.",
            ];
        }

        return null;
    }

    /**
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateMaxLength(string $field, mixed $value, array $config): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $length = (int) ($config['length'] ?? 255);

        if (is_string($value) && mb_strlen($value) > $length) {
            return [
                'field'   => $field,
                'rule'    => 'max_length',
                'message' => "The field '{$field}' must not exceed {$length} characters.",
            ];
        }

        return null;
    }

    /**
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateRegex(string $field, mixed $value, array $config): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $pattern = $config['pattern'] ?? '';

        if ($pattern && is_string($value) && ! preg_match($pattern, $value)) {
            return [
                'field'   => $field,
                'rule'    => 'regex',
                'message' => "The field '{$field}' does not match the required pattern.",
            ];
        }

        return null;
    }

    /**
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateNumericRange(string $field, mixed $value, array $config): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return [
                'field'   => $field,
                'rule'    => 'numeric_range',
                'message' => "The field '{$field}' must be numeric.",
            ];
        }

        $numericValue = (float) $value;
        $min = isset($config['min']) ? (float) $config['min'] : null;
        $max = isset($config['max']) ? (float) $config['max'] : null;

        if ($min !== null && $numericValue < $min) {
            return [
                'field'   => $field,
                'rule'    => 'numeric_range',
                'message' => "The field '{$field}' must be at least {$min}.",
            ];
        }

        if ($max !== null && $numericValue > $max) {
            return [
                'field'   => $field,
                'rule'    => 'numeric_range',
                'message' => "The field '{$field}' must not exceed {$max}.",
            ];
        }

        return null;
    }

    /**
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateInList(string $field, mixed $value, array $config): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $allowedValues = $config['values'] ?? [];

        if (! empty($allowedValues) && ! in_array($value, $allowedValues, true)) {
            $list = implode(', ', $allowedValues);

            return [
                'field'   => $field,
                'rule'    => 'in_list',
                'message' => "The field '{$field}' must be one of: {$list}.",
            ];
        }

        return null;
    }

    /**
     * @return array{field: string, rule: string, message: string}|null
     */
    protected static function validateUrl(string $field, mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && ! filter_var($value, FILTER_VALIDATE_URL)) {
            return [
                'field'   => $field,
                'rule'    => 'url',
                'message' => "The field '{$field}' must be a valid URL.",
            ];
        }

        return null;
    }
}
