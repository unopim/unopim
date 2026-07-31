<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Decimal implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Composite values reach this rule — a measurement submits an amount/unit
     * pair — and casting one would raise "array to string conversion", which
     * aborts the request instead of failing the field.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_array($value) || is_object($value)) {
            $fail('core::validation.decimal')->translate();

            return;
        }

        if (! preg_match('/^\d*(\.\d{1,4})?$/', (string) $value)) {
            $fail('core::validation.decimal')->translate();
        }
    }
}
