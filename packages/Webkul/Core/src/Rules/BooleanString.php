<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BooleanString implements ValidationRule
{
    /**
     * Validates the boolean value to be string and either "true" or "false" only
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('core::validation.boolean-string')->translate();
        }

        if (! in_array(strtolower($value), ['true', 'false'])) {
            $fail('core::validation.boolean-string')->translate();
        }
    }
}
