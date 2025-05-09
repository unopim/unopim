<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Code implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) > 255) {
            $fail('validation.max.string')->translate(['attribute' => $attribute, 'max' => 255]);
        }

        if (! preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            $fail('core::validation.code')->translate();
        }
    }
}
