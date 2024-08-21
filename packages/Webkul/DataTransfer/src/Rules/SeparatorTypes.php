<?php

namespace Webkul\DataTransfer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SeparatorTypes implements ValidationRule
{
    const SEPERATOR_TYPES = [
        ',',
        ';',
        '|',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, self::SEPERATOR_TYPES)) {
            $fail('core::validation.seperator-not-supported')->translate();
        }
    }
}
