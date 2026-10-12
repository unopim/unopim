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
        if (strlen((string) $value) > 191) {
            $fail('validation.max.string')->translate(['attribute' => $attribute, 'max' => 191]);
        }

        if (! preg_match('/^\w+$/', (string) $value)) {
            $fail('core::validation.code')->translate();
        }
    }
}
