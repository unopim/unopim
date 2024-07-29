<?php

namespace Webkul\Category\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidationTypes implements ValidationRule
{
    const VALIDATION_TYPES = [
        'number',
        'email',
        'decimal',
        'url',
        'regex',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, self::VALIDATION_TYPES)) {
            $fail('core::validation.validation-type')->translate();
        }
    }
}
