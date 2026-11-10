<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Matches a value against a regex pattern an administrator stored on a field.
 *
 * The pattern is wrapped in the same `"` delimiters the former `regex: "..."`
 * string rule used, so existing patterns behave as before. Being a rule object,
 * the pattern can never be parsed into further rule names or modifiers, and a
 * pattern that does not compile fails the field instead of raising a warning.
 */
class StoredRegexPattern implements ValidationRule
{
    public function __construct(protected string $pattern) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('validation.regex')->translate();

            return;
        }

        if (@preg_match('"'.$this->pattern.'"', (string) $value) !== 1) {
            $fail('validation.regex')->translate();
        }
    }
}
