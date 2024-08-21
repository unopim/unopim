<?php

namespace Webkul\Category\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotSupportedFields implements ValidationRule
{
    const FILED_CODES = [
        'code',
        'parent',
        'locale',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array($value, self::FILED_CODES)) {
            $fail('core::validation.not-supported')->translate();
        }
    }
}
